<?php

namespace App\Http\Controllers\Shared;

use App\Mail\TodaysReminders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;

class ReminderController extends Controller
{
	private $sources;

	function __construct()
	{
        $this->sources = [
            'Shop' => \App\Shop::all()->keyBy('id'),
            'Manager' => \App\Manager::all()->keyBy('id'),
            'Vendor' => \App\Vendor::all()->keyBy('id')
        ];
	}

	public function index()
    {
    	date_default_timezone_set('EST');

		// Get array of employees.
		$user_email_queue = \App\User::whereIn('profile',['admin','employee'])
			->get(['id','name','email'])
			->keyBy('id')
			->toArray();

		// Get Columns w/ type of reminder_date.
		$reminder_columns = \App\Column::where('type','reminder_date')
			->get();

		foreach ($reminder_columns as $reminder_column) {
			// Get all reminder_date log entries w/ today's date.
			$log_entries = \App\LogEntry::where('field_id', $reminder_column['field_id'])
				->where($reminder_column['column_name'], date("Y-m-d"))
				->get();

			foreach ($log_entries as $log_entry) {
				// Format the note.
				$reminder = [
					'name' => $this->getName($log_entry->source_class, $log_entry->source_id),
					'note' => $log_entry->log_field3
				];

				// Place the reminder in the queue of the employee who created the note.
				$user_email_queue[$log_entry->log_field1]['reminders'][$log_entry->source_class][] = $reminder;
			}
		}

		// Loop thru the user array, if they have reminders, mail them out.
		foreach ($user_email_queue as $email) {
			if (array_key_exists('reminders', $email)) {
				// return new TodaysReminders($email['reminders']);
				Mail::to($email['email'])->send(new TodaysReminders($email['reminders']));
			}
		}
    }

    private function getName($source_class, $source_id)
    {
    	$names = $this->sources[$source_class];
    	return $names[$source_id]['name'];
    }

}
