<h1>Your Reminders for {{ date("Y-m-d") }}</h1>

@foreach ($reminders as $class => $class_reminders)
<h2>{{ $class }} Reminders</h2>
	@foreach ($class_reminders as $reminder)
		<div>
			<h4>{{ $reminder['name'] }}</h4>
			<p>{{ $reminder['note'] }}</p>
		</div>
	@endforeach
@endforeach
