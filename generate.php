class ScheduleController extends Controller
{
    public function generateSchedule()
    {
        $schedules = Schedule::all();

        // Daftar hari dan waktu yang tersedia
        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $timeSlots = ['08:00-09:00', '09:00-10:00', '10:00-11:00', '11:00-12:00', '13:00-14:00'];

        // Urutkan jadwal berdasarkan jumlah jam per minggu secara menurun
        $schedules = $schedules->sortByDesc('hoursPerWeek');

        // Buat adjacency matrix berdasarkan konflik (guru atau kelas sama)
        $scheduleCount = $schedules->count();
        $adjacencyMatrix = array_fill(0, $scheduleCount, array_fill(0, $scheduleCount, 0));

        foreach ($schedules as $i => $schedule1) {
            foreach ($schedules as $j => $schedule2) {
                if ($i !== $j) {
                    if (
                        $schedule1->teacher === $schedule2->teacher || // Konflik guru
                        $schedule1->class === $schedule2->class       // Konflik kelas
                    ) {
                        $adjacencyMatrix[$i][$j] = 1; // Ada konflik
                    }
                }
            }
        }

        // Alokasi jadwal berdasarkan jumlah jam per minggu
        $finalSchedule = [];
        $usedSlots = []; // Melacak kombinasi hari dan waktu yang telah digunakan

        foreach ($schedules as $index => $schedule) {
            $remainingHours = $schedule->hoursPerWeek;

            foreach ($days as $day) {
                foreach ($timeSlots as $time) {
                    // Jika slot sudah digunakan, lewati
                    if (isset($usedSlots["$day-$time"])) {
                        continue;
                    }

                    // Periksa konflik dengan jadwal lain di slot ini
                    $conflict = false;
                    foreach ($finalSchedule as $existingSchedule) {
                        if (
                            $existingSchedule['day'] === $day &&
                            $existingSchedule['time'] === $time &&
                            ($existingSchedule['teacher'] === $schedule->teacher || $existingSchedule['class'] === $schedule->class)
                        ) {
                            $conflict = true;
                            break;
                        }
                    }

                    if ($conflict) {
                        continue;
                    }

                    // Alokasikan slot
                    $finalSchedule[] = [
                        'subject' => $schedule->subject,
                        'teacher' => $schedule->teacher,
                        'class' => $schedule->class,
                        'day' => $day,
                        'time' => $time,
                    ];
                    $usedSlots["$day-$time"] = true;

                    $remainingHours--;

                    // Jika semua jam telah dialokasikan, lanjutkan ke jadwal berikutnya
                    if ($remainingHours <= 0) {
                        break 2;
                    }
                }
            }
        }

        return response()->json($finalSchedule);
    }
}
