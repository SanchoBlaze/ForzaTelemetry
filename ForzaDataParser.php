<?php
/**
 * @author Simon McLaughlin <simon@entelechy.is>
 */

class ForzaDataParser
{
    /*
     Class variables are the specification of the format and the names of all
     the properties found in the data packet.

     Format string that allows unpack to process the data bytestream
     for the V1 format called 'sled'
     */
    //<iIfffffffffffffffffffffffffffffffffffffffffffffffffffiiiii
    private $sled_format = 'L2a/f51b/l5c';

    //## Format string for the V2 format called 'car dash'
    //                    '<iIfffffffffffffffffffffffffffffffffffffffffffffffffffiiiiifffffffffffffffffHBBBBBBbbb'
    private $dash_format = 'L2d/f51e/l5f/f17g/Sh/C6i/c3j';

    //Names of the properties in the order they're featured in the packet:
    private static array $sled_props = [
        'is_race_on',
        'timestamp_ms',
        'engine_max_rpm',
        'engine_idle_rpm',
        'current_engine_rpm',
        'acceleration_x',
        'acceleration_y',
        'acceleration_z',
        'velocity_x',
        'velocity_y',
        'velocity_z',
        'angular_velocity_x',
        'angular_velocity_y',
        'angular_velocity_z',
        'yaw',
        'pitch',
        'roll',
        'norm_suspension_travel_FL',
        'norm_suspension_travel_FR',
        'norm_suspension_travel_RL',
        'norm_suspension_travel_RR',
        'tire_slip_ratio_FL',
        'tire_slip_ratio_FR',
        'tire_slip_ratio_RL',
        'tire_slip_ratio_RR',
        'wheel_rotation_speed_FL',
        'wheel_rotation_speed_FR',
        'wheel_rotation_speed_RL',
        'wheel_rotation_speed_RR',
        'wheel_on_rumble_strip_FL',
        'wheel_on_rumble_strip_FR',
        'wheel_on_rumble_strip_RL',
        'wheel_on_rumble_strip_RR',
        'wheel_in_puddle_FL',
        'wheel_in_puddle_FR',
        'wheel_in_puddle_RL',
        'wheel_in_puddle_RR',
        'surface_rumble_FL',
        'surface_rumble_FR',
        'surface_rumble_RL',
        'surface_rumble_RR',
        'tire_slip_angle_FL',
        'tire_slip_angle_FR',
        'tire_slip_angle_RL',
        'tire_slip_angle_RR',
        'tire_combined_slip_FL',
        'tire_combined_slip_FR',
        'tire_combined_slip_RL',
        'tire_combined_slip_RR',
        'suspension_travel_meters_FL',
        'suspension_travel_meters_FR',
        'suspension_travel_meters_RL',
        'suspension_travel_meters_RR',
        'car_ordinal',
        'car_class',
        'car_performance_index',
        'drivetrain_type',
        'num_cylinders'
    ];

    //## The additional props added in the 'car dash' format
    private static array $dash_props = [
        'position_x',
        'position_y',
        'position_z',
        'speed',
        'power',
        'torque',
        'tire_temp_FL',
        'tire_temp_FR',
        'tire_temp_RL',
        'tire_temp_RR',
        'boost',
        'fuel',
        'dist_traveled',
        'best_lap_time',
        'last_lap_time',
        'cur_lap_time',
        'cur_race_time',
        'lap_no',
        'race_pos',
        'accel',
        'brake',
        'clutch',
        'handbrake',
        'gear',
        'steer',
        'norm_driving_line',
        'norm_ai_brake_diff'
    ];

    private $packet_format;

    public function __construct($data, $packet_format = 'dash')
    {
        $this->packet_format = $packet_format;

        switch ($this->packet_format) {
            case 'sled':
                $unpacked_data = unpack($this->sled_format, $data);
                $combined_data = $this->zip(self::$sled_props, $unpacked_data);
                foreach ($combined_data as $prop_name => $prop_value) {
                    $this->$prop_name = $prop_value;
                }
                break;

            case 'fh4':
                $unpacked_data = array_values(unpack($this->dash_format, $data));
                $patched_data = array_merge(array_slice($unpacked_data, 0, 232), array_slice($unpacked_data, 244, 323));
                $combined_data = array_combine(array_merge(self::$sled_props, self::$dash_props), $patched_data);
                foreach ($combined_data as $prop_name => $prop_value) {
                    $this->$prop_name = $prop_value;
                }
                break;
        }

    }

    public function to_list($attributes = null)
    {
        if (!property_exists($this->is_race_on)) {
            return array('No Data');
        }

        $return = array();
        if (is_array($attributes)) {
            foreach ($attributes as $prop_name) {
                $return[$prop_name] = $this->$prop_name;
            }
            return $return;
        }

        foreach (array_merge(self::$sled_props, self::$dash_props) as $prop_name) {
            $return[$prop_name] = $this->$prop_name;
        }

        return $return;
    }

    public static function csv_header()
    {
        return implode(',', array_merge(self::$sled_props, self::$dash_props));
    }

    public function to_csv($file = 'forza.csv', $attributes = null)
    {

        $row = array();
        if (is_array($attributes)) {
            foreach ($attributes as $prop_name) {
                $row[$prop_name] = $this->$prop_name;
            }
            return $row;
        }

        foreach (array_merge(self::$sled_props, self::$dash_props) as $prop_name) {
            $row[$prop_name] = $this->$prop_name;
        }
        return implode(',', $row);
        
    }

    /*
     * This is a Python/Ruby style zip()
     *
     * zip(array $a1, array $a2, ... array $an, [bool $python=true])
     *
     * The last argument is an optional bool that determines the how the function
     * handles when the array arguments are different in length
     *
     * By default, it does it the Python way, that is, the returned array will
     * be truncated to the length of the shortest argument
     *
     * If set to FALSE, it does it the Ruby way, and NULL values are used to
     * fill the undefined entries
     *
     */
    private function zip()
    {
        $args = func_get_args();

        $ruby = array_pop($args);
        if (is_array($ruby)) {
            $args[] = $ruby;
        }

        $counts = array_map('count', $args);
        $count = ($ruby) ? min($counts) : max($counts);
        $zipped = array();

        for ($i = 0; $i < $count; $i++) {
            for ($j = 0; $j < count($args); $j++) {
                $val = (isset($args[$j][$i])) ? $args[$j][$i] : null;
                $zipped[$i][$j] = $val;
            }
        }
        return $zipped;
    }
}

