<?php

namespace App\Helpers;

use App\Models\Alagic;
use App\Models\MenuCategory;
use App\Models\MenuCategorySenario;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use App\Models\PermissionsInRole;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\ProductSizeMenuCategory;
use App\Models\ProductSizeMenuCategoryScenario;
use App\Models\ProductSizeScenario;
use App\Models\ProductSizeScenarioToping;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class Helper
{

    public static $codes = [
        'ab' => 'Abkhazian',
        'aa' => 'Afar',
        'af' => 'Afrikaans',
        'ak' => 'Akan',
        'sq' => 'Albanian',
        'am' => 'Amharic',
        'ar' => 'Arabic',
        'an' => 'Aragonese',
        'hy' => 'Armenian',
        'as' => 'Assamese',
        'av' => 'Avaric',
        'ae' => 'Avestan',
        'ay' => 'Aymara',
        'az' => 'Azerbaijani',
        'bm' => 'Bambara',
        'ba' => 'Bashkir',
        'eu' => 'Basque',
        'be' => 'Belarusian',
        'bn' => 'Bengali',
        'bh' => 'Bihari languages',
        'bi' => 'Bislama',
        'bs' => 'Bosnian',
        'br' => 'Breton',
        'bg' => 'Bulgarian',
        'my' => 'Burmese',
        'ca' => 'Catalan, Valencian',
        'km' => 'Central Khmer',
        'ch' => 'Chamorro',
        'ce' => 'Chechen',
        'ny' => 'Chichewa, Chewa, Nyanja',
        'zh' => 'Chinese',
        'cu' => 'Church Slavonic, Old Bulgarian, Old Church Slavonic',
        'cv' => 'Chuvash',
        'kw' => 'Cornish',
        'co' => 'Corsican',
        'cr' => 'Cree',
        'hr' => 'Croatian',
        'cs' => 'Czech',
        'da' => 'Danish',
        'dv' => 'Divehi, Dhivehi, Maldivian',
        'nl' => 'Dutch, Flemish',
        'dz' => 'Dzongkha',
        'en' => 'English',
        'eo' => 'Esperanto',
        'et' => 'Estonian',
        'ee' => 'Ewe',
        'fo' => 'Faroese',
        'fj' => 'Fijian',
        'fi' => 'Finnish',
        'fr' => 'French',
        'ff' => 'Fulah',
        'gd' => 'Gaelic, Scottish Gaelic',
        'gl' => 'Galician',
        'lg' => 'Ganda',
        'ka' => 'Georgian',
        'de' => 'German',
        'ki' => 'Gikuyu, Kikuyu',
        'el' => 'Greek (Modern)',
        'kl' => 'Greenlandic, Kalaallisut',
        'gn' => 'Guarani',
        'gu' => 'Gujarati',
        'ht' => 'Haitian, Haitian Creole',
        'ha' => 'Hausa',
        'he' => 'Hebrew',
        'hz' => 'Herero',
        'hi' => 'Hindi',
        'ho' => 'Hiri Motu',
        'hu' => 'Hungarian',
        'is' => 'Icelandic',
        'io' => 'Ido',
        'ig' => 'Igbo',
        'id' => 'Indonesian',
        'ia' => 'Interlingua (International Auxiliary Language Association)',
        'ie' => 'Interlingue',
        'iu' => 'Inuktitut',
        'ik' => 'Inupiaq',
        'ga' => 'Irish',
        'it' => 'Italian',
        'ja' => 'Japanese',
        'jv' => 'Javanese',
        'kn' => 'Kannada',
        'kr' => 'Kanuri',
        'ks' => 'Kashmiri',
        'kk' => 'Kazakh',
        'rw' => 'Kinyarwanda',
        'kv' => 'Komi',
        'kg' => 'Kongo',
        'ko' => 'Korean',
        'kj' => 'Kwanyama, Kuanyama',
        'ku' => 'Kurdish',
        'ky' => 'Kyrgyz',
        'lo' => 'Lao',
        'la' => 'Latin',
        'lv' => 'Latvian',
        'lb' => 'Letzeburgesch, Luxembourgish',
        'li' => 'Limburgish, Limburgan, Limburger',
        'ln' => 'Lingala',
        'lt' => 'Lithuanian',
        'lu' => 'Luba-Katanga',
        'mk' => 'Macedonian',
        'mg' => 'Malagasy',
        'ms' => 'Malay',
        'ml' => 'Malayalam',
        'mt' => 'Maltese',
        'gv' => 'Manx',
        'mi' => 'Maori',
        'mr' => 'Marathi',
        'mh' => 'Marshallese',
        'ro' => 'Moldovan, Moldavian, Romanian',
        'mn' => 'Mongolian',
        'na' => 'Nauru',
        'nv' => 'Navajo, Navaho',
        'nd' => 'Northern Ndebele',
        'ng' => 'Ndonga',
        'ne' => 'Nepali',
        'se' => 'Northern Sami',
        'no' => 'Norwegian',
        'nb' => 'Norwegian Bokmål',
        'nn' => 'Norwegian Nynorsk',
        'ii' => 'Nuosu, Sichuan Yi',
        'oc' => 'Occitan (post 1500)',
        'oj' => 'Ojibwa',
        'or' => 'Oriya',
        'om' => 'Oromo',
        'os' => 'Ossetian, Ossetic',
        'pi' => 'Pali',
        'pa' => 'Panjabi, Punjabi',
        'ps' => 'Pashto, Pushto',
        'fa' => 'Persian',
        'pl' => 'Polish',
        'pt' => 'Portuguese',
        'qu' => 'Quechua',
        'rm' => 'Romansh',
        'rn' => 'Rundi',
        'ru' => 'Russian',
        'sm' => 'Samoan',
        'sg' => 'Sango',
        'sa' => 'Sanskrit',
        'sc' => 'Sardinian',
        'sr' => 'Serbian',
        'sn' => 'Shona',
        'sd' => 'Sindhi',
        'si' => 'Sinhala',
        'sk' => 'Slovak',
        'sl' => 'Slovenian',
        'so' => 'Somali',
        'st' => 'Sotho, Southern',
        'nr' => 'South Ndebele',
        'es' => 'Spanish, Castilian',
        'su' => 'Sundanese',
        'sw' => 'Swahili',
        'ss' => 'Swati',
        'sv' => 'Swedish',
        'tl' => 'Tagalog',
        'ty' => 'Tahitian',
        'tg' => 'Tajik',
        'ta' => 'Tamil',
        'tt' => 'Tatar',
        'te' => 'Telugu',
        'th' => 'Thai',
        'bo' => 'Tibetan',
        'ti' => 'Tigrinya',
        'to' => 'Tonga (Tonga Islands)',
        'ts' => 'Tsonga',
        'tn' => 'Tswana',
        'tr' => 'Turkish',
        'tk' => 'Turkmen',
        'tw' => 'Twi',
        'ug' => 'Uighur, Uyghur',
        'uk' => 'Ukrainian',
        'ur' => 'Urdu',
        'uz' => 'Uzbek',
        've' => 'Venda',
        'vi' => 'Vietnamese',
        'vo' => 'Volap_k',
        'wa' => 'Walloon',
        'cy' => 'Welsh',
        'fy' => 'Western Frisian',
        'wo' => 'Wolof',
        'xh' => 'Xhosa',
        'yi' => 'Yiddish',
        'yo' => 'Yoruba',
        'za' => 'Zhuang, Chuang',
        'zu' => 'Zulu'
    ];
    /**
     * test function
     */
    public static function test($test)
    {
        printf('Test',$test);
    }

    /**
     * Check Function Permission
     *
     * @param  String $Permission
     * @param  String $Role
     * @return bool
     */
    public static function checkFunctionPermission($permission)
    {
        // Get the role ID from the authenticated user
        $roleId = Auth::user()->role_id;

        // Check if the permission exists for the given role ID
        $has_permission = PermissionsInRole::where([
            ['permissions.permission', $permission],
            ['permissions_in_roles.role_id', $roleId]
        ])
            ->join('permissions', 'permissions_in_roles.permission_id', '=', 'permissions.id')
            ->join('roles', 'permissions_in_roles.role_id', '=', 'roles.id')
            ->exists();

        return $has_permission;
    }


    /**
     * Return Code of Customer FP-Token
     *
     * @return string
     */
    public static function GetCode()
    {
        $each = 2;
        for ($i = 0; $i < $each; $i++) {
            $code = random_int(100000, 999999);
            $codeCheck = User::where('remember_token', $code)->get();
            if (!isset($codeCheck[0])) {
                return $code;
            } else {
                $each++;
            }
        }
    }

    /**
     * Send Mail
     *
     * @param  array  $data
     * @param  string  $email
     * @param  string  $subject
     * @param  string  $view
     * @return bool
     */
    public static function Send_mail($data, $email, $subject, $view, $attachment = null)
    {
        $status = true;
        try {
            if($attachment == null){
                Mail::send($view, $data, function ($m) use ($email, $subject) {
                    $m->to($email, $email)->subject($subject);
                });
            }else{
                Mail::send($view, $data, function ($m) use ($email, $subject, $attachment) {
                    $m->to($email, $email)->subject($subject);

                    // Check if attachmentPath is provided and file exists
                    if ($attachment['path'] && Storage::exists($attachment['path'])) {
                        $m->attach(
                            storage_path('app/'.$attachment['path']),
                            ['as' => $attachment['file_name'], 'mime' => 'application/pdf']
                        );
                    }
                });
            }

        } catch (\Exception $ex) {
            $status = false;
        }
        if (!$status) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Return List Of Folders By Dir
     * @param  string  $path
     * @return array
     */
    public static function FolderListByPath($path)
    {
        $path = base_path($path);
        $ffs = scandir($path);
        unset($ffs[array_search('.', $ffs, true)]);
        unset($ffs[array_search('..', $ffs, true)]);
        unset($ffs[array_search('.DS_Store', $ffs, true)]);
        $returnList = array_values($ffs);
        return $returnList;
    }

    /**
     * Return Dish List By Menu Category ID
     *
     * @return array
     */
    public static function DishListByMenuCategorieID($menu_categorie_id)
    {
        $dishes = Product::where([['menu_categories_id',$menu_categorie_id],["status","true"],["type","dish"]])->orderBy('position', 'ASC')->select('id')->get();
        $returnList = [];
        foreach ($dishes as $key => $value) {
            $returnList[] = self::ProductByID($value->id);
        }
        return $returnList;
    }

    /**
     * Return Dish
     *
     * @return object
     */
    public static function ProductByID($id)
    {
        $product = Product::withTrashed()->find($id);

        $filePath = 'restaurant/dish/'.$id.'.txt';

        if (Storage::exists($filePath)) {
            $img = Storage::disk('local')->get($filePath);
            if($img != ''){
                $img = true;
            }else{
                $img = false;
            }
        }else{
            $img = false;
        }
        $product->is_image = $img;

        if ($product->is_combo === 'true') {
            $listOfSizes =  ProductSizeMenuCategory::where('products_id', $id)->get();
            foreach ($listOfSizes as $key => $value) {
                $listOfScenarios = ProductSizeMenuCategoryScenario::where('psmc_id', $value->id)->get();
                foreach ($listOfScenarios as $ke => $val) {
                    $listOfScenarios[$ke]->dishDetails = Product::find($val->products_id);
                    $listOfScenarios[$ke]->dishDetails = self::ProductByIDCombo($val->products_id);
                }
                $listOfSizes[$key]->scenarios = $listOfScenarios;
                $listOfSizes[$key]->menuCategorieDetails = MenuCategory::find($value->menu_categories_id);
            }
            $product->sizes = $listOfSizes;
        }
        if ($product->is_size === 'true') {
            $listOfSizes =  ProductSize::where('products_id', $id)->get();
            foreach ($listOfSizes as $key => $value) {
                $listOfScenarios = ProductSizeScenario::where('product_sizes_id', $value->id)->get();
                foreach ($listOfScenarios as $ke => $val) {
                    // get topping scenario tax details
                    $val->toppingTax = MenuCategorySenario::where([['menu_category_senarios.id',$val->menu_category_senarios_id]])
                            ->join('taxes','taxes.id' ,'=','menu_category_senarios.topping_tax')->select('taxes.*')->first();

                    $listOfTopings = ProductSizeScenarioToping::where('product_size_scenarios_id', $val->id)->orderBy('position', 'ASC')->get();
                    $listOfScenarios[$ke]->topings = $listOfTopings;
                }
                $listOfSizes[$key]->scenarios = $listOfScenarios;
            }
            $product->sizes = $listOfSizes;
        }
        if ($product->is_customise === 'true') {
            $listOfScenarios = ProductSizeScenario::where('products_id', $id)->get();
            foreach ($listOfScenarios as $ke => $val) {
                // get topping scenario tax details
                $val->toppingTax = MenuCategorySenario::where([['menu_category_senarios.id',$val->menu_category_senarios_id]])
                        ->join('taxes','taxes.id' ,'=','menu_category_senarios.topping_tax')->select('taxes.*')->first();

                $listOfTopings = ProductSizeScenarioToping::where('product_size_scenarios_id', $val->id)->orderBy('position', 'ASC')->get();
                $listOfScenarios[$ke]->topings = $listOfTopings;
            }
            $product->scenarios = $listOfScenarios;
        }
        // $product->discounts = self::DiscountListByMenuCategoryID($product->menu_categories_id,$product->restaurants_id);
        $product->menu_categories_id = MenuCategory::find($product->menu_categories_id);
        $product->tax = Tax::whereIn('id', explode(',', $product->tax))->get();
        $product->alagics = Alagic::whereIn('id', explode(',', $product->alagic_ids))->get();
        $product->additives = Alagic::whereIn('id', explode(',', $product->additive_ids))->get();

        $cross_selling_products = json_decode($product->cross_selling_products);
        if(is_array($cross_selling_products)){
            foreach ($cross_selling_products as $key => $value) {
                $menuCategory = MenuCategory::find($cross_selling_products[$key]->category->id);
                $cross_selling_products[$key]->category->name = $menuCategory->name;
                $cross_selling_products[$key]->category->status = $menuCategory->status;
                $cross_selling_products[$key]->category->count = Product::where([['menu_categories_id',$cross_selling_products[$key]->category->id],["status","true"],["type","dish"]])->count();
            }
        }
        $product->cross_selling_products = $cross_selling_products;

        return $product;
    }

    /**
     * Return Dish
     *
     * @return object
     */
    public static function ProductByIDCombo($id)
    {
        $product = Product::withTrashed()->find($id);
        // if ($product->is_combo === 'true') {
        //     $comboProducts = [];
        //     $listOfIds_Combo = ProductRelation::where('products_id', $id)->select('products_relation_id')->get();
        //     foreach ($listOfIds_Combo as $key => $value) {
        //         $comboProducts[] = Product::find($value->products_relation_id);
        //     }
        //     $product->combo_product_list = $comboProducts;
        // }
        if ($product->is_combo === 'true') {
            $listOfSizes =  ProductSizeMenuCategory::where('products_id', $id)->get();
            foreach ($listOfSizes as $key => $value) {
                $listOfScenarios = ProductSizeMenuCategoryScenario::where('psmc_id', $value->id)->get();
                foreach ($listOfScenarios as $ke => $val) {
                    $listOfScenarios[$ke]->dishDetails = Product::find($val->products_id);
                }
                $listOfSizes[$key]->scenarios = $listOfScenarios;
                $listOfSizes[$key]->menuCategorieDetails = MenuCategory::find($value->menu_categories_id);
            }
            $product->sizes = $listOfSizes;
        }
        if ($product->is_size === 'true') {
            $listOfSizes =  ProductSize::where('products_id', $id)->get();
            foreach ($listOfSizes as $key => $value) {
                $listOfScenarios = ProductSizeScenario::where('product_sizes_id', $value->id)->get();
                foreach ($listOfScenarios as $ke => $val) {
                    $listOfTopings = ProductSizeScenarioToping::where('product_size_scenarios_id', $val->id)->orderBy('position', 'ASC')->get();
                    $listOfScenarios[$ke]->topings = $listOfTopings;
                }
                $listOfSizes[$key]->scenarios = $listOfScenarios;
            }
            $product->sizes = $listOfSizes;
        }
        if ($product->is_customise === 'true') {
            $listOfScenarios = ProductSizeScenario::where('products_id', $id)->get();
            foreach ($listOfScenarios as $ke => $val) {
                $listOfTopings = ProductSizeScenarioToping::where('product_size_scenarios_id', $val->id)->orderBy('position', 'ASC')->get();
                $listOfScenarios[$ke]->topings = $listOfTopings;
            }
            $product->scenarios = $listOfScenarios;
        }
        // $product->discounts = self::DiscountListByMenuCategoryID($product->menu_categories_id,$product->restaurants_id);
        $product->menu_categories_id = MenuCategory::find($product->menu_categories_id);
        $product->tax = Tax::whereIn('id', explode(',', $product->tax))->get();
        $product->alagics = Alagic::whereIn('id', explode(',', $product->alagic_ids))->get();
        $product->additives = Alagic::whereIn('id', explode(',', $product->additive_ids))->get();

        $cross_selling_products = json_decode($product->cross_selling_products);
        if(is_array($cross_selling_products)){
            foreach ($cross_selling_products as $key => $value) {
                $menuCategory = MenuCategory::find($cross_selling_products[$key]->category->id);
                $cross_selling_products[$key]->category->name = $menuCategory->name;
                $cross_selling_products[$key]->category->status = $menuCategory->status;
                $cross_selling_products[$key]->category->count = Product::where([['menu_categories_id',$cross_selling_products[$key]->category->id],["status","true"],["type","dish"]])->count();
            }
        }
        $product->cross_selling_products = $cross_selling_products;

        return $product;
    }

    /**
     * Return Language List
     * @return array
     */
    public static function LanguageList()
    {
        $language_list = self::FolderListByPath('resources/lang');
        $returnArr = [];
        foreach ($language_list as $key => $value) {
            $arr = [
                "code" => $value,
                "name" => isset(self::$codes[$value]) ? self::$codes[$value]:$value
            ];
            array_push($returnArr,$arr);
        }
        return $returnArr;
    }

    public static function formatNumber($number) {
        // Split the number into integer and decimal parts
        $parts = explode('.', $number);

        // If there is no decimal part, add ".00"
        if (count($parts) == 1) {
            return $parts[0] . '.00';
        }

        // Get the integer and decimal parts
        $integerPart = $parts[0];
        $decimalPart = substr($parts[1], 0, 2); // Take only the first two digits of the decimal part

        // If the decimal part is less than two digits, pad it with zeros
        while (strlen($decimalPart) < 2) {
            $decimalPart .= '0';
        }

        return $integerPart . '.' . $decimalPart;
    }

    public static function getTaxValues($tax,$total){

        $taxRateDecimal = $tax->dine_in / 100;

        if($tax->type == 'included'){
            $taxAmount = $total * $taxRateDecimal / (1 + $taxRateDecimal);
            $amountWithoutTax = $total - $taxAmount;
        }else{
            $taxAmount = $total * $taxRateDecimal;
            $amountWithoutTax = $total;
        }
        return [ $amountWithoutTax , $taxAmount];
    }

        /**
     * Return Payment ID
     * @return string
     */
    private static function generatePaymentID() {

        $characters = array("A", "B", "C", "D", "E", "F", "G", "H", "J", "K", "L", "M", "N", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "1", "2", "3", "4", "5", "6", "7", "8", "9");

        $keys = array();
        $random_chars = '';

        while (count($keys) < 7) {
            $x = mt_rand(0, count($characters) - 1);
            if (!in_array($x, $keys)) {
                $keys[] = $x;
            }
        }

        foreach ($keys as $key) {
            $random_chars .= $characters[$key];
        }

        return $random_chars;
    }

        /**
     * Return Payment ID
     * @return array
     */
    public static function GetPaymentID()
    {
            $code = self::generatePaymentID();
            $codeCheck = Order::where('payment_id', $code)->first();
            if ($codeCheck) {
                return self::GetPaymentID();
            }
            return $code;
    }

    public static function generateUniqueId() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

}
