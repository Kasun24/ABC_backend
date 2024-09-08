<?php

namespace App\Helpers;

use App\Models\Branch;
use App\Models\GeneralSetting;
use App\Models\mcstPrice;
use App\Models\MenuCategory;
use App\Models\MenuCategorySenario;
use App\Models\MenuCategorySenarioToping;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductSize;
use App\Models\ProductSizeScenario;
use App\Models\ProductSizeScenarioToping;
use App\Models\Tax;
use App\Models\TopingScenario;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;



class GastroMasterApiHelper
{

    public static function getBranches(){
        $response = self::makeHttpRequest('/get-restaurants');
        if ($response && isset($response->status) && $response->status == true && isset($response->data)) { 
            return $response->data;
        }
        return false;
    }

    private static function clearData($branch_id){
        $products = Product::where('branch_id',$branch_id)->whereNotNull('gm_id')->where('type','dish')->get();
        foreach ($products as $product) {

            if($product->is_customise == 'true'){
                $resultPSS = ProductSizeScenario::where('products_id', $product->id)->get();
                foreach ($resultPSS as $scenario) {
                    ProductSizeScenarioToping::where('product_size_scenarios_id', $scenario->id)->forceDelete();
                    $scenario->forceDelete();
                }

            }

            if($product->is_size == 'true'){
                $resultSize = ProductSize::where('products_id', $product->id)->get();
                foreach ($resultSize as $size) {
                    $resultPSS = ProductSizeScenario::where('product_sizes_id', $size->id)->get();
                    foreach ($resultPSS as $scenario) {
                        ProductSizeScenarioToping::where('product_size_scenarios_id', $scenario->id)->forceDelete();
                        $scenario->forceDelete();
                    }
                    $size->forceDelete();
                }
            }
            //ProductPrice::where('products_id',$product->id)->forceDelete();
            $product->forceDelete();
        }

        //delete category
        $resultMenuCategories = MenuCategory::where('branch_id',$branch_id)->whereNotNull('gm_id')->get();
        foreach ($resultMenuCategories as $menuCategory) {
            $resultMCS =MenuCategorySenario::where('menu_categories_id',$menuCategory->id)->get();
            foreach ($resultMCS as $scenario) {
                $resultMCST = MenuCategorySenarioToping::where('menu_category_senarios_id', $scenario->id)->get();
                foreach ($resultMCST as $topping) {
                    mcstPrice::where('menu_category_senario_topings_id',$topping->id)->forceDelete();
                    $topping->forceDelete();
                }
                $scenario->forceDelete();
            }
            $menuCategory->forceDelete();
        }

        //delete toppings
        $products = Product::where('branch_id',$branch_id)->whereNotNull('gm_id')->where('type','toping')->get();
        foreach ($products as $product) {
            ProductPrice::where('products_id',$product->id)->forceDelete();
            $product->forceDelete();
        }
        //delete topping scenario
        TopingScenario::where('branch_id',$branch_id)->whereNotNull('gm_id')->forceDelete();
        //delete tax
        Tax::where('branch_id', $branch_id)->whereNotNull('gm_id')->forceDelete();
    }

    public static function syncData($branch_id, $forceUpdate = false)
    {
        ini_set('max_execution_time', 60 * 10);

        $branch = Branch::find($branch_id);
        if ($branch->gm_id) {
            self::clearData($branch->id); 
            self::syncTaxes($branch->id, $branch->gm_id, $forceUpdate);
            self::syncToppings($branch->id, $branch->gm_id, $forceUpdate);
            self::syncToppingScenarios($branch->id, $branch->gm_id, $forceUpdate);
            self::syncMenuCategories($branch->id, $branch->gm_id, $forceUpdate);
            self::syncDishes($branch->id, $branch->gm_id, $forceUpdate);
            self::setToppingScenarioAndCategoryIDs($branch->id, $branch->gm_id, $forceUpdate);
            self::syncMenuCategoryImages($branch->id, $branch->gm_id, $forceUpdate);
            self::syncDishImages($branch->id, $branch->gm_id, $forceUpdate);
        } else {
            return response()->json(['status' => false, 'message' =>  __('lang.api_branch_is_not_available')]);
        }
    }

    private static function setToppingScenarioAndCategoryIDs($branch_id, $gm_id, $forceUpdate){
        $products = Product::where('branch_id', $branch_id)->where('type','toping')->where('gm_id', $gm_id)->get();
        foreach ($products as $product) {
            if($product->toping_scenario_ids){
                $ids = explode(',',$product->toping_scenario_ids);
                $replacedIds = [];
                foreach ($ids as $tsid) {
                    $resultTS = TopingScenario::where('gm_id',$tsid)->first();
                    if($resultTS){
                        $replacedIds[] = $resultTS->id;
                    }
                }
                $product->toping_scenario_ids = null;
                if(!empty($replacedIds)){
                    $product->toping_scenario_ids = implode(",", $replacedIds);
                }
                $product->save();
            }

            if($product->menu_categories_ids){
                $ids = explode(',',$product->menu_categories_ids);
                $replacedIds = [];
                foreach ($ids as $tsid) {
                    $resultMC = MenuCategory::where('gm_id',$tsid)->first();
                    if($resultMC){
                        $replacedIds[] = $resultMC->id;
                    }
                }
                $product->menu_categories_ids = null;
                if(!empty($replacedIds)){
                    $product->menu_categories_ids = implode(",", $replacedIds);
                }
                $product->save();
            }

        }
    }

    private static function syncTaxes($branch_id, $gm_id, $forceUpdate){
        $response = self::makeHttpRequest('/get-taxes/' . $gm_id);
        if ($response && isset($response->status) && $response->status == true && isset($response->data)) { 
            foreach ($response->data as $row) {
                $resultTax = Tax::where('gm_id', $row->id)->first();
                if(!$resultTax){
                    $row->gm_id = $row->id;
                    unset($row->id);
                    unset($row->created_at);
                    unset($row->updated_at);
                    $row->branch_id = $branch_id;
                    unset($row->restaurants_id);
                    Tax::create(((array) $row));
                }
            }
            
        }
    }

    private static function syncMenuCategoryImages($branch_id, $gm_id, $forceUpdate)
    {
        $menuCategories = MenuCategory::where('branch_id', $branch_id)->whereNotNull('gm_id')->get();
        foreach ($menuCategories as $category) {
            $response = self::makeHttpRequest('/get-category-images/' . $category->gm_id);
            if ($response && isset($response->status) && $response->status == true && isset($response->data)) { 
                    
                    if(isset($response->data->web)){
                        self::storeImage('restaurant/menu-category/web/'.$category->id.'.png', $response->data->web);
                    }
                    if(isset($response->data->app)){
                        self::storeImage('restaurant/menu-category/app/'.$category->id.'.png', $response->data->app);
                    } 
            }
        }
    }

    private static function syncDishImages($branch_id, $gm_id, $forceUpdate)
    {
        $dishes = Product::where('branch_id', $branch_id)->whereNotNull('gm_id')->get();
        foreach ($dishes as $dish) {
            $response = self::makeHttpRequest('/get-dish-images/' . $dish->gm_id);
            if ($response && isset($response->status) && $response->status == true && isset($response->data)) {
                self::storeImage('restaurant/dish/'.$dish->id.'.png', $response->data);
            }
        }
    }

    private static function getContentTypeFromBase64($base64String) {
        // Use a regular expression to extract the content type from the base64 string
        if (preg_match('/^data:(\w+\/\w+);base64,/', $base64String, $matches)) {
            return $matches[1]; // Return the content type
        }
        return null; // Return null if no content type is found
    }

    private static function createImageFromBase64($base64String) {
        // Remove the data URI scheme part
        $base64String = preg_replace('/^data:image\/\w+;base64,/', '', $base64String);
        
        // Decode the base64 string
        $decodedData = base64_decode($base64String);
        
        // Ensure the base64 string was decoded successfully
        if ($decodedData === false) {
            throw false;
        }
        
        // Create an image from the decoded data
        try {
            $image = \imagecreatefromstring($decodedData);
        } catch (\Exception $th) {
            return false;
        }
       
        
        // Ensure the image was created successfully
        if ($image === false) {
            return false;
        }
        
        return $image;
    }

    
    private static function storeImage($path, $content){
        // Get the image type
        $mimeType = self::getContentTypeFromBase64($content);
        if (in_array($mimeType, ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'])) {

            $image = self::createImageFromBase64($content);
            if($image){
                Storage::disk('local')->put($path, '');
                // Save as PNG
                ob_start();
                imagepng($image);
                $pngData = ob_get_clean();
                Storage::disk('local')->put($path, $pngData);
                imagedestroy($image);
            }
        }
    }

    private static function syncToppings($branch_id, $gm_id, $forceUpdate)
    {
        $response = self::makeHttpRequest('/get-toppings/' . $gm_id);
        $count = 0;
        if ($response && isset($response->status) && $response->status == true && isset($response->data)) {

            //store data
            foreach ($response->data as $row) {
                //find if already available
                $result = Product::where('gm_id', $row->id)->first();

                //replace id to gm_id, unset created_at, updated_at
                $row->gm_id = $row->id;
                unset($row->id);
                unset($row->created_at);
                unset($row->updated_at);

                $product_prices = $row->product_prices;
                unset($row->product_prices);
                unset($row->restaurants_id);
                $row->branch_id = $branch_id;
                //set tax
                if(isset($row->tax) && $row->tax){
                    $tax = Tax::where('gm_id',$row->tax)->first();
                    if($tax){
                        $row->tax = $tax->id;
                    }else{
                        $row->tax = null;
                    }
                }

                if (!$result) {
                    //store this
                    $product = Product::create(((array) $row));
                    $count++;

                    if ($product && $product->id) {
                        foreach ($product_prices as $product_price) {
                            $resultPrice = ProductPrice::where('gm_id', $product_price->id)->first();
                            if (!$resultPrice) {
                                $product_price->gm_id = $product_price->id;

                                unset($product_price->id);
                                unset($product_price->restaurants_id);
                                $product_price->branch_id = $branch_id;
                                $product_price->products_id = $product->id;
                                unset($product_price->created_at);
                                unset($product_price->updated_at);
                                ProductPrice::create(((array) $product_price));
                            }
                        }
                    }
                } else {
                    if ($forceUpdate) {
                        //todo update
                    }
                }
            }
        }

        return $count;
    }

    private static function syncToppingScenarios($branch_id, $gm_id, $forceUpdate)
    {
        $response = self::makeHttpRequest('/get-topping-scenarios/' . $gm_id);
        $count = 0;
        if ($response && isset($response->status) && $response->status == true && isset($response->data)) {

            foreach ($response->data as $row) {
                $result = TopingScenario::where('gm_id', $row->id)->first();
                if (!$result) {
                    $row->gm_id = $row->id;
                    unset($row->id);
                    unset($row->created_at);
                    unset($row->updated_at);
                    unset($row->restaurants_id);
                    $row->branch_id = $branch_id;

                    //set tax
                    if(isset($row->tax) && $row->tax){
                        $tax = Tax::where('gm_id',$row->tax)->first();
                        if($tax){
                            $row->tax = $tax->id;
                        }else{
                            $row->tax = null;
                        }
                    }

                    TopingScenario::create(((array) $row));
                    $count++;
                } else {
                    if ($forceUpdate) {
                        //todo update
                    }
                }
            }
        }

        return $count;
    }

    private static function syncMenuCategories($branch_id, $gm_id, $forceUpdate)
    {
        $response = self::makeHttpRequest('/get-menu-categories/' . $gm_id);
        $count = 0;
        if ($response && isset($response->status) && $response->status == true && isset($response->data)) {

            foreach ($response->data as $row) {

                $result = MenuCategory::where('gm_id', $row->id)->first();
                if (!$result) {

                    $row->gm_id = $row->id;
                    unset($row->id);
                    unset($row->created_at);
                    unset($row->updated_at);
                    unset($row->restaurants_id);
                    $row->branch_id = $branch_id;
                    $menu_category_senarios = $row->menu_category_senarios;
                    unset($row->menu_category_senarios);
                    unset($row->main_categories_id);
                    //set tax
                    if(isset($row->tax) && $row->tax){
                        $tax = Tax::where('gm_id',$row->tax)->first();
                        if($tax){
                            $row->tax = $tax->id;
                        }else{
                            $row->tax = null;
                        }
                    }
                    $createdMenuCategory = MenuCategory::create(((array) $row));

                    if ($createdMenuCategory && $createdMenuCategory->id) {
                        foreach ($menu_category_senarios as $menu_category_senario) {

                            $resultMCS = MenuCategorySenario::where('gm_id', $menu_category_senario->id)->first();
                            if (!$resultMCS) {

                                //find toping scenario
                                $topingScenario = TopingScenario::where('gm_id', $menu_category_senario->toping_scenarios_id)->first();
                                if ($topingScenario) {
                                    $menu_category_senario->gm_id = $menu_category_senario->id;
                                    unset($menu_category_senario->id);
                                    unset($menu_category_senario->created_at);
                                    unset($menu_category_senario->updated_at);

                                    $menu_category_senario->toping_scenarios_id = $topingScenario->id;
                                    $menu_category_senario->menu_categories_id = $createdMenuCategory->id;
                                    $menu_category_senario_topings = $menu_category_senario->menu_category_senario_topings;
                                    unset($menu_category_senario->menu_category_senario_topings);

                                    $createdMenuCategoryScenario = MenuCategorySenario::create(((array) $menu_category_senario));
                                    if ($createdMenuCategoryScenario && $createdMenuCategoryScenario->id) {
                                        foreach ($menu_category_senario_topings as $menu_category_senario_toping) {

                                            $resultTopping = Product::where('gm_id', $menu_category_senario_toping->products_id)->first();
                                            if ($resultTopping) {
                                                $menu_category_senario_toping->gm_id = $menu_category_senario_toping->id;
                                                unset($menu_category_senario_toping->id);
                                                $menu_category_senario_toping->menu_category_senarios_id = $createdMenuCategoryScenario->id;
                                                $menu_category_senario_toping->products_id = $resultTopping->id;
                                                unset($menu_category_senario_toping->created_at);
                                                unset($menu_category_senario_toping->updated_at);
                                                $mcst_prices = $menu_category_senario_toping->mcst_prices;
                                                unset($menu_category_senario_toping->mcst_prices);

                                                $createdMCST = MenuCategorySenarioToping::create(((array) $menu_category_senario_toping));
                                                if ($createdMCST && $createdMCST->id) {
                                                    foreach ($mcst_prices as $mcst_price) {
                                                        $resultMCSTP = mcstPrice::where('gm_id', $mcst_price->id)->first();
                                                        if (!$resultMCSTP) {
                                                            $mcst_price->gm_id = $mcst_price->id;
                                                            unset($mcst_price->id);
                                                            unset($mcst_price->created_at);
                                                            unset($mcst_price->updated_at);
                                                            $mcst_price->menu_category_senario_topings_id = $createdMCST->id;
                                                            mcstPrice::create(((array) $mcst_price));
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    $count++;
                } else {
                    if ($forceUpdate) {
                        //todo update
                    }
                }
            }
        }

        return $count;
    }

    private static function storeProductScenarios($product_size_scenario)
    {
        $resultPSS = ProductSizeScenario::where('gm_id', $product_size_scenario->id)->first();
        if (!$resultPSS) {
            $product_size_scenario->gm_id = $product_size_scenario->id;
            unset($product_size_scenario->id);
            //find menu_category_senarios_id
            $resultMCS = MenuCategorySenario::where('gm_id', $product_size_scenario->menu_category_senarios_id)->first();
            if ($resultMCS) {
                $product_size_scenario->menu_category_senarios_id = $resultMCS->id;
                unset($product_size_scenario->created_at);
                unset($product_size_scenario->updated_at);
                $product_size_scenario_topings = $product_size_scenario->product_size_scenario_topings;
                unset($product_size_scenario->product_size_scenario_topings);

                $createdProductSizeScenario = ProductSizeScenario::create(((array) $product_size_scenario));
                if ($createdProductSizeScenario && $createdProductSizeScenario->id) {
                    foreach ($product_size_scenario_topings as $product_size_scenario_toping) {
                        $resultMCST = MenuCategorySenarioToping::where('gm_id', $product_size_scenario_toping->mcs_topings_id)->first();
                        $resultMCSTP = mcstPrice::where('gm_id', $product_size_scenario_toping->mcst_prices_id)->first();

                        $resultPSST = ProductSizeScenarioToping::where('gm_id', $product_size_scenario_toping->id)->first();

                        if ($resultMCST && $resultMCST->id && $resultMCSTP && $resultMCSTP->id) {
                            if (!$resultPSST) {
                                $product_size_scenario_toping->gm_id = $product_size_scenario_toping->id;
                                unset($product_size_scenario_toping->id);
                                $product_size_scenario_toping->product_size_scenarios_id = $createdProductSizeScenario->id;
                                $product_size_scenario_toping->mcs_topings_id = $resultMCST->id;
                                $product_size_scenario_toping->mcst_prices_id = $resultMCSTP->id;
                                unset($product_size_scenario_toping->created_at);
                                unset($product_size_scenario_toping->updated_at);
                                //unset
                                $menu_category_senario_topings = $product_size_scenario_toping->menu_category_senario_topings;
                                $mcst_prices = $product_size_scenario_toping->mcst_prices;
                                unset($product_size_scenario_toping->menu_category_senario_topings);
                                unset($product_size_scenario_toping->mcst_prices);
                                //set tax
                                if(isset($product_size_scenario_toping->tax) && $product_size_scenario_toping->tax){
                                    $tax = Tax::where('gm_id',$product_size_scenario_toping->tax)->first();
                                    if($tax){
                                        $product_size_scenario_toping->tax = $tax->id;
                                    }else{
                                        $product_size_scenario_toping->tax = null;
                                    }
                                }
                                ProductSizeScenarioToping::create(((array) $product_size_scenario_toping));
                            }
                        }
                    }
                }
            }
        }
    }

    private static function syncDishes($branch_id, $gm_id, $forceUpdate)
    {
        $menuCategories = MenuCategory::where('branch_id', $branch_id)->whereNotNull('gm_id')->get();
        foreach ($menuCategories as $menuCategory) {
            $data = self::getDishesByCategory($menuCategory);
            if ($data) {
                //store dish if not available
                foreach ($data as $row) {
                    $result = Product::where('gm_id', $row->id)->first();
                    if (!$result) {
                        $row->gm_id = $row->id;
                        unset($row->id);
                        $resultMenuCategory = MenuCategory::where('gm_id', $row->menu_categories_id)->first();
                        if ($resultMenuCategory && $resultMenuCategory->id) {
                            $row->menu_categories_id = $resultMenuCategory->id;
                            unset($row->created_at);
                            unset($row->updated_at);


                            $product_sizes = [];
                            $product_size_scenarios = [];
                            if ($row->is_customise == 'true') {
                                $product_size_scenarios = $row->product_size_scenarios;
                                unset($row->product_size_scenarios);
                            }

                            if ($row->is_size == 'true') {
                                $product_sizes = $row->product_sizes;
                                unset($row->product_sizes);
                            }
                            $product_prices = $row->product_prices;
                            unset($row->product_prices);
                            $row->branch_id = $gm_id;
                            unset($row->restaurants_id);
                            $createdProduct = Product::create(((array) $row));

                            if ($createdProduct && $createdProduct->id) {

                                //store product prices if available
                                foreach ($product_prices as $product_price) {
                                    $product_price->gm_id = $product_price->id;
                                    unset($product_price->id);
                                    unset($product_price->created_at);
                                    unset($product_price->updated_at);
                                    $product_price->branch_id = $gm_id;
                                    unset($product_price->restaurants_id);
                                    $product_price->products_id = $createdProduct->id;
                                    ProductPrice::create(((array) $product_price));
                                }
                                //store scenario if customize
                                if ($row->is_customise == 'true') {
                                    //scenario store
                                    foreach ($product_size_scenarios as $product_size_scenario) {
                                        $product_size_scenario->products_id = $createdProduct->id;
                                        self::storeProductScenarios($product_size_scenario);
                                    }
                                }
                                //store scenario if size
                                if ($row->is_size == 'true') {
                                    //scenario store
                                    foreach ($product_sizes as $product_size) {
                                        $resultPSize = ProductSize::where('gm_id', $product_size->id)->first();
                                        if (!$resultPSize) {
                                            $product_size->gm_id = $product_size->id;
                                            unset($product_size->id);
                                            unset($product_size->created_at);
                                            unset($product_size->updated_at);
                                            $product_size->products_id = $createdProduct->id;
                                            $product_size_scenarios = $product_size->product_size_scenarios;
                                            unset($product_size->product_size_scenarios);
                                            unset($product_size->points_per_size);
                                            $createdProductSize = ProductSize::create(((array) $product_size));
                                            if ($createdProductSize && $createdProductSize->id) {
                                                foreach ($product_size_scenarios as $product_size_scenario) {
                                                    $product_size_scenario->product_sizes_id = $createdProductSize->id;
                                                    self::storeProductScenarios($product_size_scenario);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    private static function getDishesByCategory($category)
    {
        $response = self::makeHttpRequest('/get-dishes/' . $category->gm_id);
        if ($response && isset($response->status) && $response->status == true && isset($response->data)) {
            return $response->data;
        }

        return null;
    }

    private static function makeHttpRequest($endpoint, $type = 'get', $data = [])
    {
        $settings = GeneralSetting::first();
        if ($settings->api_endpoint && $settings->sec_token) {
            $response = Http::withToken($settings->sec_token)->{$type}($settings->api_endpoint . $endpoint, $data);
            if ($response->successful()) {
                $responseData = $response->json();
                return json_decode(json_encode($responseData));
            } else {
                Log::info(__('t-make_http_request_failed_to_send_data_status') . $response->status());
            }
        } else {
            Log::info(__('t-make_http_request_no_api_endpoint_or_sec_token'));
        }
        return false;
    }
}
