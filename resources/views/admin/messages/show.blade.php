@extends('admin.layouts.app')

@section('content')

<div class="flex h-[80vh] bg-white rounded shadow">

<!-- LISTE CONVERSATIONS -->

<div class="w-1/3 border-r overflow-y-auto">

<h2 class="text-xl font-bold p-4 border-b">
Conversations
</h2>

@foreach($trips as $t)

<a href="{{ route('admin.messages.show',$t->id) }}"

class="block p-4 border-b hover:bg-gray-100
@if($trip->id==$t->id) bg-gray-200 @endif
">

<div class="font-bold">

{{ $t->user->first_name ?? 'User supprimé' }}

→

{{ $t->driver->first_name ?? 'Driver supprimé' }}

</div>

<div class="text-sm text-gray-500">

Trip #{{ $t->id }}

</div>

</a>

@endforeach

</div>



<!-- MESSAGES -->

<div class="flex-1 flex flex-col">

<!-- HEADER -->

<div class="p-4 border-b font-bold">

{{ $trip->user->first_name ?? '' }}

↔

{{ $trip->driver->first_name ?? '' }}

| Trip #{{ $trip->id }}

</div>



<!-- LISTE MESSAGES -->

<div class="flex-1 overflow-y-auto p-4" id="messagesBox">

@foreach($messages as $message)

<div class="mb-4">

@if(str_contains($message->sender_type,'User'))

<div class="text-left">

<div class="inline-block bg-gray-200 p-3 rounded">

{{ $message->content }}

</div>

</div>

@else

<div class="text-right">

<div class="inline-block bg-blue-500 text-white p-3 rounded">

{{ $message->content }}

</div>

</div>

@endif

</div>

@endforeach

</div>

</div>

</div>

@endsection


@section('scripts')

<script>

// Scroll automatique en bas

let box = document.getElementById("messagesBox");

box.scrollTop = box.scrollHeight;


// Refresh toutes les 5 secondes

setInterval(function(){

location.reload();

},5000);

</script>

@endsection