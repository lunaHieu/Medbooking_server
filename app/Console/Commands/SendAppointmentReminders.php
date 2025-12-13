<?php

namespace App\Console\Commands;
use App\Models\Notification;
use App\Models\Appointment;
use Illuminate\Console\Command;
use Carbon\Carbon;
class SendAppointmentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-appointments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Quét lịch ngày mai và tạo thông báo in-app';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tomorrow = Carbon::tomorrow();
        $appointments = Appointment::whereDate('StartTime', $tomorrow)
            ->where('Status', 'Confirmed')
            ->with(['doctor.user', 'patient'])
            ->get();
        $count = 0;
        foreach ($appointments as $appt) {
            $doctorName = $appt->doctor && $appt->doctor->user ? $appt->doctor->user->FullName : 'Bác sĩ';
            $time = Carbon::parse($appt->StartTime)->format('H:i');
            $title = "Nhắc nhở lịch khám";
            $content = "Bạn có lịch khám với $doctorName vào lúc $time ngày mai.";
            $exists = Notification::where('UserID', $appt->PatientID)
                ->where('Content', $content)
                ->exists();
            if (!$exists) {
                Notification::create([
                    'UserID' => $appt->PatientID,
                    'Title' => $title,
                    'Content' => $content,
                    'NotificationType' => 'Reminder',
                    'Channel' => 'in_app',
                    'Status' => 'Unread',
                    'AppointmentID' => $appt->AppointmentID
                ]);
                $count++;
            }
        }
        $this->info("Đã tạo $count thông báo nhắc hẹn in-app.");
    }
}
