<?php
namespace App\Http\Collectors;

class ApplicationSettingCollector {
    public function CollectBlockedDate($req) {
        return [
            'name' => $req->name,
            'type' => 'blocked_date',
            'value' => new \DateTime($req->date)
        ];
    }
}

?>
