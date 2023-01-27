<?php
namespace App\Http\Repos;

use App\ApplicationSetting;

class ApplicationSettingsRepo {
    public function Delete($appSettingId) {
        $appSetting = ApplicationSetting::where('id', $appSettingId);

        return $appSetting->delete();
    }

    public function GetByType($type) {
        $settings = ApplicationSetting::where('type', $type);

        return $settings->get();
    }

    public function Insert($appSetting) {
        $new = new ApplicationSetting;

        return $new->create($appSetting);
    }
}

?>

