<?php

namespace App\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Settings;
use App\Models\Time;
use App\Services\OnlineOrderingHours;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class TimeController extends Controller
{
    public function index()
    {
        $gettime = Time::all();
        $settingsdata = Settings::first();
        $onlineHoursReady = Schema::hasColumn('time', 'online_order_until');
        return view('admin.time', compact('gettime', 'settingsdata', 'onlineHoursReady'));
    }
    public function store(Request $request)
    {
        $onlineHoursReady = Schema::hasColumn('time', 'online_order_until');
        $request->validate([
            'day' => ['required','array','size:7'],
            'day.*' => ['required','distinct','in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'always_close' => ['required','array','size:7'], 'always_close.*' => ['required','in:1,2'],
            'open_time' => ['required','array','size:7'], 'open_time.*' => ['required','string','max:20'],
            'close_time' => ['required','array','size:7'], 'close_time.*' => ['required','string','max:20'],
            'break_start' => ['required','array','size:7'], 'break_start.*' => ['required','string','max:20'],
            'break_end' => ['required','array','size:7'], 'break_end.*' => ['required','string','max:20'],
            'online_order_until' => [$onlineHoursReady ? 'required' : 'prohibited','array','size:7'],
            'online_order_until.*' => ['required','date_format:H:i'],
        ]);
        foreach (['day','always_close','open_time','close_time','break_start','break_end', ...($onlineHoursReady ? ['online_order_until'] : [])] as $field) {
            if (!array_is_list($request->input($field))) throw ValidationException::withMessages([$field => 'A napi időpontok sorrendje érvénytelen. Töltsd újra az oldalt.']);
        }
        if ($onlineHoursReady) foreach ($request->day as $key => $day) {
            if ((int) $request->always_close[$key] !== 2) continue;
            $reset = strtolower($request->close_time[$key]) === 'closed';
            if (!OnlineOrderingHours::validCutoff($reset ? '12:00am' : $request->open_time[$key], $reset ? '11:30pm' : $request->close_time[$key], $request->online_order_until[$key])) {
                throw ValidationException::withMessages(['online_order_until.'.$key => 'Az online rendelés vége a napi nyitás után és legkésőbb a záráskor lehet.']);
            }
        }
        return DB::transaction(function () use ($request, $onlineHoursReady) {
            $settingsdata = Settings::first();
            if (empty($settingsdata)) {
                $settingsdata = new Settings();
            }
            $settingsdata->interval_time = $request->interval_time;
            $settingsdata->interval_type = $request->interval_type;
            $settingsdata->perslot_booking_limit = $request->slot_limit;
            $settingsdata->ordertype_date_time = $request->ordertypedatetime;
            $settingsdata->save();

            $day = $request->day;
            $open_time = $request->open_time;
            $break_start = $request->break_start;
            $break_end = $request->break_end;
            $close_time = $request->close_time;
            $always_close = $request->always_close;
            foreach ($day as $key => $no) {
                $input['day'] = $no;
                if ($onlineHoursReady) $input['online_order_until'] = $request->online_order_until[$key];
                if ($always_close[$key] == "2") {
                    if (strtolower($close_time[$key]) == "closed") {
                        $input['open_time'] = "12:00am";
                        $input['break_start'] = "12:00pm";
                        $input['break_end'] = "01:00pm";
                        $input['close_time'] = "11:30pm";
                    } else {
                        $input['open_time'] = $open_time[$key];
                        $input['break_start'] = $break_start[$key];
                        $input['break_end'] = $break_end[$key];
                        $input['close_time'] = $close_time[$key];
                    }
                } else {
                    $input['open_time'] = "12:00am";
                    $input['break_start'] = "12:00pm";
                    $input['break_end'] = "01:00pm";
                    $input['close_time'] = "11:30pm";
                }
                $input['always_close'] = $always_close[$key];
                Time::where('day', $no)->update($input);
            }
            return redirect()->back()->with('success', trans('messages.success'));
        });
    }
}
