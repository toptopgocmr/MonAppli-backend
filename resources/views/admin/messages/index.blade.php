@extends('admin.layouts.app')

@section('content')

<div class="flex h-[80vh] bg-white rounded shadow">

<!-- LISTE CONVERSATIONS -->

<div class="w-1/3 border-r overflow-y-auto">

<h2 class="text-xl font-bold p-4 border-b">
Conversations
</h2>

@foreach($trips as $trip)

<a href="{{ route('admin.messages.show',$trip->id) }}"

class="block p-4 border-b hover:bg-gray-100">

<div class="font-bold">

{{ $trip->user->first_name }}

→

{{ $trip->driver->first_name }}

</div>

<div class="text-sm text-gray-500">

Trip #{{ $trip->id }}

</div>

</a>

@endforeach

</div>


<!-- ZONE MESSAGES -->

<div class="flex-1 flex items-center justify-center text-gray-400">

Sélectionnez une conversation

</div>


</div>

@endsection