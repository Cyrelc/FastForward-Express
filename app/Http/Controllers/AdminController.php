<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Hash;

    use App\Http\Models\Admin;

    Class AdminController extends Controller {
        public function load() {
            $path = __DIR__."/../../../config/ffe_config/adminSettings.xml";
            $xml = simplexml_load_file($path);
            $factory = new Admin\AdminModelFactory();
            $model = $factory->GetEditModel($xml->GST);
            return view('admin.adminSettings', compact('model'));
        }

        public function storeGST(Request $req) {
            $path = __DIR__."/../../../config/ffe_config/adminSettings.xml";
            $xml = simplexml_load_file($path);
            $xml->GST = $req->input('gst_percent');
            $xml->asXML($path);
        }
        
        public function hashPassword(Request $req) {
            return Hash::make(preg_replace('/\s+/', '', $req->password));
        }
    }
?>
