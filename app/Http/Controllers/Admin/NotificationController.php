<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{

/*
|--------------------------------------------------------------------------
| LISTE NOTIFICATIONS
|--------------------------------------------------------------------------
*/

public function index(Request $request)
{

$notifications = $request->user()
->notifications()
->latest()
->paginate(20);

return response()->json($notifications);

}



/*
|--------------------------------------------------------------------------
| COMPTEUR NOTIFICATIONS NON LUES
|--------------------------------------------------------------------------
*/

public function unreadCount(Request $request)
{

$count = $request->user()
->unreadNotifications()
->count();

return response()->json([

'unread'=>$count

]);

}



/*
|--------------------------------------------------------------------------
| MARQUER UNE NOTIFICATION LUE
|--------------------------------------------------------------------------
*/

public function markRead(Request $request,$id)
{

$request->user()

->notifications()

->where('id',$id)

->update([

'read_at'=>now()

]);

return response()->json([

'message'=>'Notification lue'

]);

}



/*
|--------------------------------------------------------------------------
| MARQUER TOUT LIRE
|--------------------------------------------------------------------------
*/

public function markAllRead(Request $request)
{

$request->user()

->unreadNotifications

->markAsRead();

return response()->json([

'message'=>'Toutes les notifications lues'

]);

}

}