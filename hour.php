class ScheduleController extends Controller
{
    public function generateSchedule()
    {
        $schedules = Schedule::orderBy('hoursPerWeek', 'desc')->get();

        // Daftar hari dan waktu yang tersedia
        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $timeSlots = ['08:00-09:00', '09:00-10:00', '10:00-11:00', '11:00-12:00', '13:00-14:00'];

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

        // Derajat simpul
        $degrees = [];
        foreach ($adjacencyMatrix as $i => $row) {
            $degrees[$i] = array_sum($row);
        }

        // Urutkan simpul berdasarkan derajat secara menurun
        arsort($degrees);
        $sortedVertices = array_keys($degrees);

        // Pewarnaan graf
        $colors = [];
        foreach ($sortedVertices as $vertex) {
            $usedColors = [];
            foreach ($adjacencyMatrix[$vertex] as $neighbor => $isConnected) {
                if ($isConnected && isset($colors[$neighbor])) {
                    $usedColors[] = $colors[$neighbor];
                }
            }

            // Cari warna yang tersedia
            $color = 1;
            while (in_array($color, $usedColors)) {
                $color++;
            }
            $colors[$vertex] = $color;
        }

        // Penjadwalan berdasarkan warna graf dan jumlah jam per minggu
        $finalSchedule = [];
        $slotTracker = []; // Melacak jam yang sudah terpakai di setiap hari

        foreach ($colors as $index => $color) {
            $dayIndex = ($color - 1) % count($days);
            $timeIndex = floor(($color - 1) / count($days)) % count($timeSlots);

            $day = $days[$dayIndex];
            $time = $timeSlots[$timeIndex];

            // Pastikan slot tersedia berdasarkan jumlah jam per minggu
            $schedule = $schedules[$index];
            $hoursRemaining = $schedule->hoursPerWeek;

            if (!isset($slotTracker[$schedule->class][$day])) {
                $slotTracker[$schedule->class][$day] = 0;
            }

            while ($hoursRemaining > 0 && $slotTracker[$schedule->class][$day] < count($timeSlots)) {
                $time = $timeSlots[$slotTracker[$schedule->class][$day]];
                $finalSchedule[] = [
                    'subject' => $schedule->subject,
                    'teacher' => $schedule->teacher,
                    'class' => $schedule->class,
                    'day' => $day,
                    'time' => $time,
                ];

                $hoursRemaining--;
                $slotTracker[$schedule->class][$day]++;
            }

            // Update jadwal ke database
            $schedule->update(['day' => $day, 'time' => $time]);
        }

        return response()->json($finalSchedule);
    }
}
