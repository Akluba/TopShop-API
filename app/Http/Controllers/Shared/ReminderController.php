<?php

namespace App\Http\Controllers\Shared;

use App\Mail\TodaysReminders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;

class ReminderController extends Controller
{
	private $shops;
	private $managers;

	public function __construct()
	{
		$this->shops = \App\Shop::all();
		$this->managers = \App\Manager::all();
	}

	public function index()
    {
    	date_default_timezone_set('EST');

		$user_email_queue = \App\User::whereIn('profile',['admin','employee'])
			->get(['id','name','email'])
			->keyBy('id')
			->toArray();

		$reminder_columns = \App\Column::where('type','reminder_date')->get();
		foreach ($reminder_columns as $reminder_column) {

			$log_entries = \App\LogEntry::where('field_id', $reminder_column['field_id'])
				->where($reminder_column['column_name'], date("Y-m-d"))
				->get();

			foreach ($log_entries as $log_entry) {
				$reminder = [
					'name' => $this->getName($log_entry->source_class, $log_entry->source_id),
					'note' => $log_entry->log_field3
				];

				if ($log_entry->source_class === 'Shop') {
					$user_email_queue[$log_entry->log_field1]['reminders']['shops'][] = $reminder;
				} else {
					$user_email_queue[$log_entry->log_field1]['reminders']['managers'][] = $reminder;
				}
			}
		}

		foreach ($user_email_queue as $email) {
			if (array_key_exists('reminders', $email)) {
				// return new TodaysReminders($email['reminders']);
				Mail::to($email['email'])->send(new TodaysReminders($email['reminders']));
			}
		}
    }

    private function getName($source_class, $source_id)
    {
    	$names = $source_class === 'Shop' ? $this->shops : $this->managers;
    	$names = $names->keyBy('id');

    	$name_key = $source_class === 'Shop' ? 'shop_name' : 'manager_name';

    	return $names[$source_id][$name_key];
    }

}
