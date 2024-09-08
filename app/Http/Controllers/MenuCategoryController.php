<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\MenuCategory;
use App\Models\Tax;
use Illuminate\Http\Request;

class MenuCategoryController extends Controller
{
    /**
     * Return Category List
     *
     * @param  Request  $request
     * @return string
     */
    public function category_list(Request $request)
    {

        /*$permission_in_roles = Helper::checkFunctionPermission('language_add');
        if (!$permission_in_roles) {
            return abort('403');
        }*/

        $request->validate([
            'branch_id' => 'required'
        ]);

        $i = 0;
        $countDishes = 4;
        $menuCategory = MenuCategory::where([['branch_id', $request->branch_id], ['status', "true"]])->orderBy('position', 'ASC')->get();
        $returnList = [];

        foreach ($menuCategory as $key => $value) {

            $dishes = Helper::DishListByMenuCategorieID($value->id);
            if ($i < $countDishes) {
                $value->dishes = $dishes;
            } else {
                $value->dishes = [];
            }

            // get category tax details if available
            if ($value->tax) {
                $categoryTax = Tax::find($value->tax);
                $value->categoryTaxDetails = $categoryTax;
            }

            $arrIndexDete = ['MON' => 0, 'TUE' => 1, 'WED' => 2, 'THU' => 3, 'FRI' => 4, 'SAT' => 5, 'SUN' => 6];
            $visibility = json_decode($value->visibility);
            $visibility = $visibility[$arrIndexDete[strtoupper(date('D'))]];
            if ($visibility->isActive === true) {
                if ($visibility->timeSlot->from === 'All Day') {
                    $returnList[] = $value;
                } else {
                    $now_time = date('H:i');
                    if ($visibility->timeSlot->from != '' && $visibility->timeSlot->to != '') {
                        if ((strtotime($visibility->timeSlot->from) <= strtotime($now_time) && strtotime($now_time) <= strtotime($visibility->timeSlot->to))) {
                            if (count($dishes) > 0 && $i < $countDishes) {
                                $returnList[] = $value;
                            } else {
                                $returnList[] = $value;
                            }
                        }
                        /**
                         * When pre order is enabled and the restauratn is closed, some of menu categories not visible in the app,
                         * coz those categories are not available at the moment, but when pre order is enabled and menu category avilable time not reached yet, then
                         * we can display and let em pre order those categories. To do that this else part was implemented..
                         */
                        else {
                            // current time is before the menu category available time and restaurant has preorder enabled and the restaurant must be closed to trigger this condition
                            if (strtotime($now_time) <= strtotime($visibility->timeSlot->from)) {
                                if (count($dishes) > 0 && $i < $countDishes) {
                                    $returnList[] = $value;
                                } else {
                                    $returnList[] = $value;
                                }
                            }
                        }
                    } else {
                        if (count($dishes) > 0 && $i < $countDishes) {
                            $returnList[] = $value;
                        } else {
                            $returnList[] = $value;
                        }
                    }
                }
            }
        }

        return $returnList;
    }

    /**
     * Return Category List
     *
     * @param  Request  $request
     * @return string
     */
    public function fetchCategory(Request $request)
    {

        $branch_id = $request->header('Branch');
        $length = $request->input('length') ? $request->input('length') : 10;
        $sortBy = $request->input('column') ? $request->input('column') : 'id';
        $orderBy = $request->input('dir') ? $request->input('dir') : 'asc';
        $searchValue = $request->input('search') ? $request->input('search') : '';

        $menuCategories = MenuCategory::where([['branch_id', $branch_id], ['name', 'like', '%' . $searchValue . '%']])
            ->orderBy($sortBy, $orderBy)
            ->paginate($length);

        foreach ($menuCategories as $menuCategory) {
            $menuCategory->tax_details = Tax::find(+$menuCategory->tax);
        }
        return response()->json(['status' => true, 'data' => $menuCategories]);
    }

    /**
     * Return Category List
     *
     * @param  Request  $request
     * @return string
     */
    public function category_list_for_qr_app(Request $request, $branch_id)
    {

        /*$permission_in_roles = Helper::checkFunctionPermission('language_add');
        if (!$permission_in_roles) {
            return abort('403');
        }*/

        $menuCategories = MenuCategory::where([['branch_id', $branch_id], ['status', "true"]])->orderBy('position', 'ASC')->get();

        return $menuCategories;
    }

    /**
     * Return dishes of the given menu category id
     *
     * @param  Request  $request
     * @return string
     */
    public function dish_list_of_the_given_category(Request $request)
    {

        $request->validate([
            'menu_category_ids' => 'required'
        ]);

        $menuCategories = MenuCategory::whereIn('id', $request->menu_category_ids)->get();

        if ($menuCategories && isset($menuCategories)) {
            foreach ($menuCategories as $category) {
                $dishes = Helper::DishListByMenuCategorieID($category->id);
                $category->dishes = $dishes;
            }
            return response()->json(['status' => true, 'data' => $menuCategories]);
        } else {
            return response()->json(['status' => false, 'msg' => __('lang.t-menu_category_not_found')]);
        }
    }
}
