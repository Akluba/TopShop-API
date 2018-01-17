<h1>Your Reminders for {{ date("Y-m-d") }}</h1>

<h2>Shop Reminders</h2>
@foreach ($shop_reminders as $reminder)
	<div>
		<h4>{{ $reminder['name'] }}</h4>
		<p>{{ $reminder['note'] }}</p>
	</div>
@endforeach

<h2>Manager Reminders</h2>
@foreach ($manager_reminders as $reminder)
	<div>
		<h4>{{ $reminder['name'] }}</h4>
		<p>{{ $reminder['note'] }}</p>
	</div>
@endforeach