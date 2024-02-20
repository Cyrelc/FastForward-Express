<?php
namespace App\Http\Repos;

use App\Models\ApplicationSetting;

class ApplicationSettingsRepo {
    public function Delete($appSettingId) {
        $appSetting = ApplicationSetting::where('id', $appSettingId);

        return $appSetting->delete();
    }

    public function GetByType($type) {
        $settings = ApplicationSetting::where('type', $type);

        return $settings->get();
    }

    public function GetUpcomingHolidays($days = 60) {
        $startDate = new \DateTime();
        $endDate = (new \DateTime())->modify('+' . $days . 'days');

        $holidays = ApplicationSetting::where('type', 'blocked_date')
            ->whereDate('value', '>=', $startDate)
            ->whereDate('value', '<=', $endDate)
            ->select(
                'name',
                'value',
            );

        return $holidays->get();
    }

    public function Insert($appSetting) {
        $new = new ApplicationSetting;

        return $new->create($appSetting);
    }
}

?>

