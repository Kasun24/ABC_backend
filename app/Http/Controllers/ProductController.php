<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Tax;
use App\Models\TopingScenario;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Return Dish List
     *
     * @param  Request  $request
     * @return string
     */
    public function fetchProduct(Request $request)
    {
        $branch_id = $request->header('Branch');
        $length = $request->input('length') ? $request->input('length') : 10;
        $sortBy = $request->input('column') ? $request->input('column') : 'id';
        $orderBy = $request->input('dir') ? $request->input('dir') : 'asc';
        $searchValue = $request->input('search') ? $request->input('search') : '';
        // category filter id
        $menuCategoryId = json_decode($request->input('category'));
        // took where statement out to make it dynamic
        $whereStatement = [['name', 'like', '%' . $searchValue . '%'], ['branch_id',$branch_id], ['type', 'dish']];
        // add extra where statement if menucategoryid filter has a value      
        if (isset($menuCategoryId) && isset($menuCategoryId->id) && !empty($menuCategoryId->id)) {
            array_push($whereStatement, ['menu_categories_id', $menuCategoryId->id]);
        }
        $dishes = Product::where($whereStatement)
            ->orderBy($sortBy, $orderBy)
            ->paginate($length);

        // Get dish tax details, for each is kinda dumb but this is more secure than modifing the existing code
        // by Pumayk26 at 2023-10-06    
        foreach ($dishes as $key => $dish) {
            if($dish->tax){
                $taxDetails = Tax::where('id',$dish->tax)
                ->where('branch_id',$branch_id)
                ->first();
                $dish->taxDetails = $taxDetails;
            }
        }
        return response()->json(['status' => true,'data' => $dishes]);
    }


    /**
     * Return Scenario List
     *
     * @param  Request  $request
     * @return string
     */
    public function scenario_list(Request $request){

        $branch_id = $request->header('Branch');
        // $length = $request->input('length') ? $request->input('length') : 10;
        $sortBy = $request->input('column') ? $request->input('column') : 'id';
        $orderBy = $request->input('dir') ? $request->input('dir') : 'asc';
        $searchValue = $request->input('search') ? $request->input('search') : '';


        $scenarios = TopingScenario::where([['branch_id',$branch_id],['name', 'like', '%' . $searchValue . '%']])
        ->select('id','name','position')
        ->orderBy($sortBy,$orderBy)
        ->get();

        foreach ($scenarios as $scenario) {
            if($scenario->tax){
                $scenario->tax_details = Tax::find($scenario->tax);
            }
        }
        return response()->json(['status' => true,'data' => $scenarios]);
    }


    /**
     * Return Toping List
     *
     * @param  Request  $request
     * @return string
     */
    public function toping_list(Request $request){

        $branch_id = $request->header('Branch');
        $length = $request->input('length') ? $request->input('length') : 10;
        $sortBy = $request->input('column') ? $request->input('column') : 'id';
        $orderBy = $request->input('dir') ? $request->input('dir') : 'asc';
        $searchValue = $request->input('search') ? $request->input('search') : '';
        $senarioId = json_decode($request->input('senario'));
        $categoryId = json_decode($request->input('category'));
        $where = [['branch_id',$branch_id],['type','toping']];
        
        $topings = Product::where($where);
        if (isset($senarioId) && isset($senarioId->id) && !empty($senarioId->id) && $senarioId->id != "all") {
            $topings->whereRaw('FIND_IN_SET(?, toping_scenario_ids)', [$senarioId->id]);
        }
        if(isset($categoryId) && isset($categoryId->id) && !empty($categoryId->id) && $categoryId->id != "all"){
            $topings->whereRaw('FIND_IN_SET(?, menu_categories_ids)', [$categoryId->id]);
        }
        $topings->where('name', 'like', '%' . $searchValue . '%');
        $topings->orderBy($sortBy,$orderBy);
        $topings = $topings->paginate($length);

        return response()->json(['status' => true,'data' => $topings]);
    }

}
