<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Official;
use App\Announcement;
use Illuminate\Http\Request;

class PublicController extends Controller {

    public function getOfficialList() {
        $official = Official::orderBy('created_at', 'desc')->get();
        return response()->json($official, 200);
    }

    public function getEventAnnouncementList() {
        $announcement = Announcement::where('hidden', 0)->orderBy('created_at', 'desc')->get();

        $newannouncement = [];
        foreach ($announcement as $key => $value) {
            $newannouncement[] = $value;
            $newannouncement[$key]['content'] = strip_tags($value['content']);
        }

        return response()->json($announcement, 200);
    }

    public function EAsingle(Request $request, $id) {
        $ea = Announcement::where('id', $id)->first();
        $ea['content'] = nl2br($ea['content']);
        return response()->json($ea, 200);
    }

}
