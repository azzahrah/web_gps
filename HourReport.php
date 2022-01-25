<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class HourReport
{

    //Current engine state
    private $engine_state;
    private $tracks;
    private $track;
    public $total_on;
    public $total_off;
    //Minimum duration park/run
    private $minDuration;
    private $from;
    private $to;
    private $vh_id;
    private $imei;
    private $json;

    function __construct($nopol, $vh_id, $from, $to)
    {
        $this->nopol = $nopol;
        $this->vh_id = $vh_id;
        $this->from = $from;
        $this->to = $to;

        $this->engine_state = 0;
        $this->tracks = array();
        $this->minDuration = 10 * 60;
    }

    function execute()
    {
        global $mysqli;
        $query = "select * from `tracks_report` where vh_id='" . $this->vh_id . "' and  tdate>='" . $this->from . "' AND tdate<='" . $this->to . "' ORDER by tdate ASC";
        $result = $mysqli->query($query);
        if ($result) {
            $first = true;
            while ($row = $result->fetch_assoc()) {
                if ((int)$row['speed'] >= 5) {
                    $row['acc'] = 1;
                }
                if (!$first) {
                    if ($this->track->acc == (int)$row['acc']) {
                        $this->updateTrack($row);
                    } else {
                        $this->track->end = $row['tdate'];
                        $this->saveToTrack($this->minDuration);
                        $this->addTrack($row);
                    }
                } else {
                    $this->addTrack($row);
                    $first = false;
                }
            }
            $result->free();
        }
        $this->total_on = 0;
        $this->total_off = 0;
        foreach ($this->tracks as $key => $value) {
            if ($value->acc == 1) {
                $this->total_on += $value->second;
            } else {
                $this->total_off += $value->second;
            }
        }
        $this->finish();
        $mysqli->close();
        $summary = array();
        array_push($summary, ['description' => 'Total On', 'value' => sec_to_time($this->total_on)]);
        array_push($summary, ['description' => 'Total Off', 'value' => sec_to_time($this->total_off)]);

        $this->json['total'] = count($this->tracks);
        $this->json['total_on'] = sec_to_time($this->total_on);
        $this->json['total_off'] = sec_to_time($this->total_off);
        $this->json['summary'] = $summary;
        $this->json['rows'] = count($this->tracks) > 0 ? $this->tracks : array();
    }

    function getJson()
    {
        return json_encode($this->json);
    }

    function getExcel()
    {
        $spreadsheet = new Spreadsheet();
        // Set document properties
        $spreadsheet->getProperties()->setCreator("Joko Pitoyo")
            ->setLastModifiedBy("Joko Pitoyo")
            ->setTitle("GPS Tracking Report")
            ->setSubject("GPS Tracking Trip Report")
            ->setDescription("GPS Tracking Report")
            ->setKeywords("GPS Tracking Report")
            ->setCategory("GPS Tracking Report");
        //tdate,speed,acc,park,angle,alarm,lat,lng,poi,add
        $headerTitle = array('Mesin', 'Mulai', 'Selesai', 'Durasi', 'POI', 'Alamat Awal', 'Alamat Akhir');
        $columns = array('A', 'B', 'C', 'D', 'E', 'F', 'G');
        $columnsWidth = array(10, 20, 20, 20, 20, 60, 70);
        $styleArray = array(
            'borders' => array(
                'outline' => array(
                    'style' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '00000000'),
                ),
            ),
        );

        // Rename worksheet
        $spreadsheet->setActiveSheetIndex(0);
        $spreadsheet->getActiveSheet()->setTitle('LAPORAN JAM KERJA');


        //Set Width
        for ($i = 0; $i < count($columns); $i++) {
            $spreadsheet->getActiveSheet()->getColumnDimension($columns[$i])->setWidth($columnsWidth[$i]);
        }

        //Set Title Header
        for ($i = 0; $i < count($headerTitle); $i++) {
            $spreadsheet->setActiveSheetIndex(0)->setCellValue($columns[$i] . '6', $headerTitle[$i]);
        }

        //set Border
        $spreadsheet->getActiveSheet()->getStyle('A6:G6')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFCABDBD');
        // Add some data
        $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue('A1', 'LAPORAN JAM KERJA')
            ->setCellValue('A2', 'NOPOL:' . $this->nopol)
            ->setCellValue('A3', 'PERIODE:' . $this->from . ' S/D ' . $this->to)
            ->setCellValue('A4', 'MESIN ON:' . sec_to_time($this->total_on))
            ->setCellValue('A5', 'MESIN OFF:' . sec_to_time($this->total_off));

        $startRow = 7;

        while (count($this->tracks) > 0) {
            $trip = array_shift($this->tracks);
            $spreadsheet->setActiveSheetIndex(0)
                ->setCellValue('A' . $startRow, $trip->status)
                ->setCellValue('B' . $startRow, $trip->begin)
                ->setCellValue('C' . $startRow, $trip->end)
                ->setCellValue('D' . $startRow, $trip->duration)
                ->setCellValue('E' . $startRow, $trip->poi)
                ->setCellValue('F' . $startRow, $trip->addr_begin)
                ->setCellValue('G' . $startRow, $trip->addr_end);
            $startRow++;
        }


        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="hour_report_' . $this->nopol . '_' . $this->from . '_' . $this->to . '.xls"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
        $writer->save('php://output');
        exit;
    }

    function finish()
    {
        if (!isset($this->track))
            return;
        $this->saveToTrack(0);
    }

    function removeTrack()
    {
        if (!isset($this->track)) {
            return;
        }
        unset($this->track->acc);
        unset($this->track->begin);
        unset($this->track->end);
        unset($this->track->poi);
        unset($this->track->addr_begin);
        unset($this->track->addr_end);
        unset($this->track);
    }

    function addTrack($track)
    {
        $t = new HourInfo();
        $t->begin = $track['tdate'];
        $t->end = $track['tdate'];
        $t->acc = $track['acc'];
        $t->poi = $track['poi'];
        $t->addr_begin = $track['address'];
        $this->track = $t;
    }

    function updateTrack($newtrack)
    {
        if ((int)$newtrack['acc'] == (int)$this->track->acc) {
            $this->track->end = $newtrack['tdate'];
            $this->track->addr_end = $newtrack['address'];
        } else {
            $this->track->end = $newtrack['tdate'];
            $this->track->addr_end = $newtrack['address'];
            $this->saveToTrack($this->minDuration);
            $this->addTrack($newtrack);
        }
    }

    function getPreviousTrack()
    {
        return count($this->tracks) > 0 ? $this->tracks[count($this->tracks) - 1] : null;
    }

    function saveToTrack($minDuration)
    {
        $finish = new DateTime($this->track->end);
        $start = new DateTime($this->track->begin);
        $diff = $finish->diff($start);
        $second = $diff->d * 24 * 60 * 60;
        $second += $diff->h * 60 * 60;
        $second += $diff->m * 60;
        $second += $diff->s;
        $this->track->second = $second;
        $this->track->duration = $this->getDuration($diff);
        if ($this->track->second >= 10) {
            $trackInfo = new HourInfo();
            $trackInfo->acc = $this->track->acc;
            $trackInfo->status = $this->track->acc == 1 ? "ON" : "OFF";
            $trackInfo->begin = $this->track->begin;
            $trackInfo->end = $this->track->end;
            $trackInfo->poi = $this->track->poi;
            $trackInfo->addr_begin = $this->track->addr_begin;
            $trackInfo->addr_end = $this->track->addr_end;

            $trackInfo->second = $this->track->second;
            $trackInfo->duration = $this->track->duration;
            $trackInfo->complete = true;
            if ((int)$this->track->acc == 1) {
                $this->total_on += $this->track->second;
            } else {
                $this->total_off += $this->track->second;
            }
            array_push($this->tracks, $trackInfo);
        }
        $this->removeTrack();
        return true;
    }

    function getDuration($interval)
    {
        $duration = "";
        if ($interval->d > 0) {
            $duration = " Hari ";
        }
        if ($interval->h > 0) {
            $duration .= $interval->h . " Jam ";
        }
        if ($interval->i > 0) {
            $duration .= $interval->i . " Menit ";
        }
        if ($interval->s > 0) {
            $duration .= $interval->s . " Detik ";
        }
        return $duration;
    }

    function calculate()
    {
        $finish = new DateTime($this->track->end);
        $start = new DateTime($this->track->begin);
        $diff = $finish->diff($start);
        $second = $diff->d * 24 * 60 * 60;
        $second += $diff->h * 60 * 60;
        $second += $diff->m * 60;
        $second += $diff->s;
        $this->track->second = $second;
    }

    function getTracks()
    {
        return $this->tracks;
    }
}

class HourInfo
{
    public $status;
    public $acc;
    public $begin;
    public $end;
    public $poi;
    public $addr_begin;
    public $addr_end;
    public $second;
    public $duration;
    public $complete;

    function __construct()
    {
        $this->complete = false;
        $this->status = "OFF";
        $this->acc = 0;
        $this->second = 0;
        $this->duration = "";
        $this->poi = "";
        $this->addr_begin = "";
        $this->addr_end = "";
    }
}
