<?php
    namespace App\Http\Models\Admin;
    
    use App\Http\Models\Admin;

    class AdminModelFactory{
        public function GetEditModel($gst) {
			$model = new AdminFormModel();
            $model->GST = $gst;
            return $model;
        }
    }
?>