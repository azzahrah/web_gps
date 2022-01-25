<?php

class ParkReport
{
    public $vh_id;
    public $from;
    public $to;
    public $json;
    public $detection_mode;
    public $min_speed;
    public $tracks;
    const DETECT_BY_ACC = 1;
    const DETECT_BY_SPEED = 2;
    const DETECT_BY_ACC_AND_SPEED = 3;

    function __construc()
    {
        // $this->vh_id = $vh_id;
        // $this->from = $from;
        // $this->to = $to;
        // $this->detection_mode = $detection_mode;
        // $this->min_speed = $min_speed;
        $this->tracks = array();
        $this->json['code'] = '';
        $this->json['msg'] = '';
        $this->json['total'] = 0;
        $this->json['rows'] = array();
    }

    function execute()
    {
        global $_POST;
        global $mysqli;

        $this->vh_id = isset($_POST['vh_id']) ? intval($_POST['vh_id']) : 0;
        $this->from = isset($_POST['from']) ? $mysqli->real_escape_string($_POST['from']) : '';
        $this->to = isset($_POST['to']) ? $mysqli->real_escape_string($_POST['to']) : '';
        $this->detection_mode = isset($_POST['detection_mode']) ? intval($_POST['detection_mode']) : 1;
        $this->min_speed = isset($_POST['min_speed']) ? intval($_POST['min_speed']) : 5;
        if ($this->from == '' || $this->to == '') {
            $this->json['msg'] = 'Tanggal Tidak Boleh Kosoong';
            $mysqli->close();
            return;
        }
        if ($this->vh_id <= 0) {
            $this->json['msg'] = 'Kendaraan Boleh Kosoong';
            $mysqli->close();
            return;
        }

        $stmt = $mysqli->prepare("CALL trip_report(?,?,?)");
        $stmt->bind_param("iss", $this->vh_id, $this->from, $this->to);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result) {
            $this->json['msg'] = $mysqli->error;
            $mysqli->close();
            return;
        }

        $first = true;
        $last = null;
        $prevPark = false;
        while ($row = $result->fetch_assoc()) {
            if (!$first) {
                $ispark = $this->is_park((int)$row['acc'], (int)$row['speed']);
                if ($ispark && $prevPark) {
                    $last['tdate2'] = $row['tdate'];
                } else  if ($ispark && !$prevPark) {
                    $last = $row;
                    $prevPark = true;
                } else {
                    $last['tdate2'] = $row['tdate'];
                    $last['second'] = $this->get_diff_second($last['tdate'], $last['tdate2']);
                    $last['duration'] = $this->get_duration($last['second']);
                    array_push($this->tracks, $last);
                    unset($last);
                    $prevPark = false;
                }
            } else {
                $ispark = $this->is_park((int)$row['acc'], (int)$row['speed']);
                if ($ispark) {
                    $last = $row;
                    $first = false;
                    $prevPark = true;
                }
            }
        }
        if ($prevPark) {
            $last['second'] = $this->get_diff_second($last['tdate'], $last['tdate2']);
            $last['duration'] = $this->get_duration($last['second']);
            array_push($this->tracks, $last);
            unset($last);
            $prevPark = false;
        }
        $mysqli->close();
    }
    /*
    @param $begin first date
    @param $end last date
    */

    function get_diff_second($begin, $end)
    {
        $finish = new DateTime($end);
        $start = new DateTime($begin);
        $diff = $finish->diff($start);
        $second = $diff->d * 24 * 60 * 60;
        $second += $diff->h * 60 * 60;
        $second += $diff->m * 60;
        $second += $diff->s;
        return $second;
    }
    function get_duration($interval)
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
    function is_park($acc, $speed)
    {
        if ($this->detection_mode == self::DETECT_BY_ACC) {
            return $acc == 0;
        } else if ($this->detection_mode == self::DETECT_BY_SPEED) {
            return $acc < $this->min_speed;
        }
    }

    function toJson()
    {
        $this->json['rows'] = $this->tracks;
        echo json_encode($this->json);
    }
}
