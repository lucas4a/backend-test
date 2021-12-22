<?php

namespace App\Http\Controllers;

use App\Models\Investment;
use Illuminate\Http\Request;
use DateTimeZone;
use DateTime;
use Illuminate\Support\Facades\Validator;

class InvestmentController extends Controller
{
    /**
     * Create new investment.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request){
        $dtz = new DateTimeZone("America/Fortaleza");
        $now = new DateTime("now", $dtz);

        $validator = Validator::make($request->all(), [
            'owner'                 => 'required|integer',
            'initial_amount'        => 'required|numeric|gte:0',
            'creation'              => 'required|date|before_or_equal:' . $now->format("Y-m-d")
        ]);

        if ($validator->fails()) {
            return response(json_encode($validator->errors()), 400)
                            ->header('Content-Type', 'application/json');
        }

        $investmentId = Investment::create([
            'owner'                 => $request->owner,
            'creation'              => $request->creation,
            'gains_last_updated_at' => $request->creation,
            'amount'                => $request->initial_amount,
            'initial_amount'        => $request->initial_amount,
        ]);

        if($investmentId){
            return response("Sucess", 200)->header('Content-Type', 'application/json');
        }
        else{
            return response("Failed", 500)->header('Content-Type', 'application/json');
        }
    }

    /**
     * View one investment.
     *
     * @return \Illuminate\Http\Response
     */
    public function view(Request $request){
        $validator = Validator::make($request->all(), [
            'investment'    => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response(json_encode($validator->errors()), 400)
                            ->header('Content-Type', 'application/json');
        }

        $investment = Investment::find($request->investment);

        if(!$investment){
            return response("Investment not found", 400)->header('Content-Type', 'application/json');
        }
        
        $this->updateGains(Investment::find($request->investment));

        $investment = Investment::find($request->investment);

        $response = [];
        $response['initial_amount'] = $investment->initial_amount;
        $response['expected_balance'] = $investment->amount;
        
        return response($response, 200)->header('Content-Type', 'application/json');
    }

    /**
     * Update the gains of an investment.
     * 
     * @param Investmen Id of investment.
     */
    public function updateGains(Investment $investment){
        $dtz = new DateTimeZone("America/Fortaleza");
        $now = new DateTime("now", $dtz);

        $gains_last_updated_at = DateTime::createFromFormat('Y-m-d', $investment->gains_last_updated_at)->setTimezone($dtz);

        $interval = $gains_last_updated_at->diff($now);
        $months_since_last_update = intval($interval->format("%m"));

        for($i = 0; $i < $months_since_last_update; $i++){
            $old_gains = $investment->amount;
            $new_gains = $old_gains * (0.52 / 100);
            $investment->amount += $new_gains;
        }

        $new_updated_at = DateTime::createFromFormat('Y-m-d', $now->format("Y-") . $now->format("m-") . $gains_last_updated_at->format("d"))->setTimezone($dtz);

        $investment->gains_last_updated_at = $new_updated_at->format("Y-m-d");
        $investment->save();
    }
}