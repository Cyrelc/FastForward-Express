<?php

namespace App\Http\Services;

use JWadhams\JsonLogic;

/**
 * Lets one rule ask whether another rule matched.
 *
 * The context carries a `matched_rules` list - the ids of every rule on the sheet whose condition
 * evaluated true for this delivery - so a rule can be written as "Matched Rules NOT CONTAINS [Basic
 * Weight 0-49 lb] AND weight < 99". That is the same multiselect/CONTAINS shape as zone tags and zone
 * groups, so no custom json-logic operator is needed and nothing special happens in the query builder.
 *
 * "Matched" means the rule's CONDITION was true - not that it produced a charge. The distinction is
 * real: with first-set-wins, a Set Base Price rule can match and still be skipped because a
 * higher-ranked rule already claimed the base. Defining it as "matched" keeps a reference a pure
 * function of the delivery rather than of evaluation order, which is what makes the ordering below
 * possible at all.
 *
 * Because references are pure, rules do not have to be authored in any particular order: conditions
 * are evaluated in dependency order (topological), so a rule always sees its prerequisites resolved.
 * Priority stays purely about pricing order and has nothing to do with this.
 */
class RuleReferences {
    const CONTEXT_KEY = 'matched_rules';

    /**
     * The rule ids one rule's json_logic refers to.
     *
     * Only literals sitting directly alongside a {"var": "matched_rules"} count. That precision
     * matters: a rule is usually "matched_rules NOT CONTAINS [7] AND total_weight_lbs < 99", and
     * scooping every integer out of the tree would read the 99 as a reference to rule 99.
     */
    public static function ReferencedIds($jsonLogic) {
        $logic = is_string($jsonLogic) ? json_decode($jsonLogic, true) : $jsonLogic;

        $ids = [];
        self::Walk($logic, $ids);

        return array_values(array_map('intval', array_keys($ids)));
    }

    private static function Walk($node, array &$ids) {
        if(!is_array($node))
            return;

        if(array_is_list($node)) {
            foreach($node as $child)
                self::Walk($child, $ids);
            return;
        }

        foreach($node as $operator => $operands) {
            $operandList = (is_array($operands) && array_is_list($operands)) ? $operands : [$operands];

            $appliesToMatchedRules = false;
            foreach($operandList as $operand)
                if(self::IsMatchedRulesVar($operand))
                    $appliesToMatchedRules = true;

            if($appliesToMatchedRules) {
                // The ids are whatever literals accompany the var, e.g.
                // {"some": [{"var": "matched_rules"}, {"in": [{"var": ""}, [12, 13]]}]}
                foreach($operandList as $operand)
                    if(!self::IsMatchedRulesVar($operand))
                        self::CollectIds($operand, $ids);

                continue;
            }

            self::Walk($operands, $ids);
        }
    }

    private static function IsMatchedRulesVar($node) {
        return is_array($node) && ($node['var'] ?? null) === self::CONTEXT_KEY;
    }

    private static function CollectIds($node, array &$ids) {
        if(is_int($node) || (is_string($node) && ctype_digit($node))) {
            $ids[(int) $node] = true;
            return;
        }

        if(!is_array($node))
            return;

        foreach($node as $key => $child) {
            // {"var": ""} is json-logic's "the current item" inside a `some`; it names no rule.
            if($key === 'var')
                continue;
            self::CollectIds($child, $ids);
        }
    }

    /**
     * Rules in dependency order: a rule always appears after everything it references.
     *
     * @param $references - id => [referenced ids]
     * @param $names      - id => name, only so a cycle can be reported in words
     *
     * @throws \RuntimeException when the references form a cycle
     */
    public static function DependencyOrder(array $references, array $names = []) {
        $order = [];
        $state = [];

        $visit = function($id, $path) use (&$visit, &$order, &$state, $references, $names) {
            $current = $state[$id] ?? 0;

            if($current === 2)
                return;

            if($current === 1) {
                $loop = array_map(
                    fn($stepId) => $names[$stepId] ?? ('#' . $stepId),
                    array_merge(array_slice($path, array_search($id, $path)), [$id])
                );
                throw new \RuntimeException('Rules refer to each other in a circle: ' . implode(' -> ', $loop));
            }

            $state[$id] = 1;

            foreach($references[$id] ?? [] as $referencedId)
                // A reference to a rule that no longer exists is ignored here. Deleting a referenced
                // rule is blocked at the controller, so this only happens to data that predates that
                // guard - and a dangling reference simply never matches.
                if(isset($references[$referencedId]))
                    $visit($referencedId, array_merge($path, [$id]));

            $state[$id] = 2;
            $order[] = $id;
        };

        foreach(array_keys($references) as $id)
            $visit($id, []);

        return $order;
    }

    /**
     * Evaluates every rule's condition against the context, in dependency order, and writes the ids
     * that matched into the context as `matched_rules`.
     *
     * The context is filled in progressively as we go, which is safe precisely because of the
     * ordering: by the time a rule is evaluated, every rule it references has been. Rules it does not
     * reference may still be missing from the list, but it cannot observe them.
     *
     * @param $rules   - list of ['id' => int, 'name' => string, 'json_logic' => string|array]
     * @param $context - the pricing (object) or timing (array) context; mutated in place
     *
     * @return array of matched rule ids
     */
    public static function ResolveMatched(array $rules, &$context) {
        $references = [];
        $names = [];
        $logicById = [];

        foreach($rules as $rule) {
            $id = (int) $rule['id'];
            $references[$id] = self::ReferencedIds($rule['json_logic']);
            $names[$id] = $rule['name'] ?? ('#' . $id);
            $logicById[$id] = is_string($rule['json_logic'])
                ? json_decode($rule['json_logic'], true)
                : $rule['json_logic'];
        }

        $matched = [];

        foreach(self::DependencyOrder($references, $names) as $id) {
            self::WriteMatched($context, $matched);

            if($logicById[$id] && JsonLogic::apply($logicById[$id], $context))
                $matched[] = $id;
        }

        self::WriteMatched($context, $matched);

        return $matched;
    }

    private static function WriteMatched(&$context, array $matched) {
        if(is_object($context))
            $context->{self::CONTEXT_KEY} = $matched;
        else
            $context[self::CONTEXT_KEY] = $matched;
    }

    /**
     * Which of $rules reference $id. Used to refuse a delete that would silently change what the
     * remaining rules mean - drop the middle weight bracket and the next one quietly widens to cover
     * its range, with no error and a wrong price on every affected bill.
     *
     * @return array of names
     */
    public static function Dependents(array $rules, $id) {
        $dependents = [];

        foreach($rules as $rule)
            if(in_array((int) $id, self::ReferencedIds($rule['json_logic']), true))
                $dependents[] = $rule['name'] ?? ('#' . $rule['id']);

        return $dependents;
    }
}
