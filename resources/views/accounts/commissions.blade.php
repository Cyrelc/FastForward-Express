<div class="clearfix">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Basic Info</h3>
            </div>
            <div class='panel-body'>
<!-- Commissions -->
                <div class="col-lg-12 col-nopadding">
                    @include('partials.commission', [
                        'commission' => count($model->commissions) > 0 ? $model->commissions[0] : null,
                        'prefix' => 'commission-1',
                        'employees' => $model->employees,
                        'title' => 'Commission 1'
                    ])

                    @include('partials.commission', [
                        'commission' => count($model->commissions) > 1 ? $model->commissions[1] : null,
                        'prefix' => 'commission-2',
                        'employees' => $model->employees,
                        'title' => 'Commission 2'
                    ])
                </div>
            </div>
        </div>
    </div>
</div>
