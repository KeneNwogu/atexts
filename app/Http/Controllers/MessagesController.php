<?php

namespace App\Http\Controllers;

use App\Http\Resources\MessageResource;
use App\Models\Message;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;

class MessagesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return MessageResource::collection(Auth::user()->messages);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'sender_id' => ['uuid', 'exists:users,id'],
                'recipient_id' => ['required', 'uuid', 'exists:users,id'],
                'body' => ['required', 'string'],
            ]);

            $token = explode(" ", $request->headers->get('Authorization'))[1] ?? null;

            if ($token) {
                $user = Sanctum::personalAccessTokenModel()::findToken($token)->tokenable;

                if (
                    array_key_exists('sender_id', $data) &&
                    $user->id !== $data['sender_id']
                ) throw new Exception('You are not authorized to send messages on behalf of this user.');

                $data['sender_id'] = $user->id;
            }

            $message = Message::create($data);

            return new MessageResource($message);
        } catch (Exception $exception) {
            return response()->json([
                'data' => null,
                'errors' => $exception->getMessage()
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
//        $message = Auth::user()->messages()->findOrFail($id);
        try {
            $message = Message::find($id);

            if ($message) return new MessageResource($message);
            else return response()->json([
                'error' => 'Unable to fetch message',
                'data' => null
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'error' => 'Unable to fetch message',
                'data' => null
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $message = Auth::user()->messages()->findOrFail($id);

        $message->delete();

        return new MessageResource($message);
    }
}
