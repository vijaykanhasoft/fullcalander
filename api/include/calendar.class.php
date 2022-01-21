
<?php

class calendar {

    protected $_db;
    protected $_global;
    protected $_reminderText = array("0" => "At Time of Start", "5" => '5 Minute before start', "15" => '15 Minute before start', "30" => '30 Minute before start', "60" => '1 Hour before start', "120" => '2 Hours before start', "1440" => '1 Day before start', "2880" => '2 Days before start');
    protected $_email;
    protected $_admin;
    protected $_history;

    public function __construct() {
        global $userData;
//   $this->_db = new db();
        $this->_db = db::getInstance();
        //  print_r($this->_db);
        $this->_global = $userData;
    }
 
     public function getSetting($user_id) {
            $this->_db->where('user_id', $user_id);
            $res = $this->_db->get('acd_calendar_settings');
            return $res;
    }

     public function getcolor($type, $user_id, $own_id, $color_type) {

        $user = $this->getSetting($own_id);

        if (sizeof($user) != 0) {
            $json = json_decode($user[0]['value'], true);

            if (isset($json['users'])) {
                $keys = array_keys($json['users']);

                if ($type == 'users') {
                    $keys = array_keys($json['users']);

                    if (in_array($user_id, $keys)) {
                        return $json['users'][$user_id][$color_type];
                    } else {
                        return '#ff0000';
                    }
                } else {
                    return $json['own'][$color_type];
                }
            } else {

                if ($type == 'own') {
                    return $json['own'][$color_type];
                } else {
                    return '#ff0000';
                }
            }
        } else {
            return '#ff0000';
        }
    }

    // GET USER LIST.
    public function getUsers($user_id) {
        $this->_db->where('user_id !=' . $user_id); // FOR LOGIN USER
        $this->_db->where("is_active!=-1"); // GET ONLY ACTIVE USER.
        $result = $this->_db->get('acd_users');
        $admin = array();
        for ($c = 0; $c < sizeof($result); $c++) {
            if ($result[$c]['role_id'] == 1) {
                $result[$c]['color_event'] = $this->getcolor('users', $result[$c]['user_id'], $user_id, 'event');
                $result[$c]['color_task'] = $this->getcolor('users', $result[$c]['user_id'], $user_id, 'task');
                $admin[1][] = $result[$c]; // SAVE EXECUTIVE LIST ARRAY.
            } else if ($result[$c]['role_id'] == 2) {
                $result[$c]['color_event'] = $this->getcolor('users', $result[$c]['user_id'], $user_id, 'event');
                $result[$c]['color_task'] = $this->getcolor('users', $result[$c]['user_id'], $user_id, 'task');
                $admin[2][] = $result[$c]; // SAVE MANAGER LIST IN ARRAY.
            } else if ($result[$c]['role_id'] == 3) {
                $result[$c]['color_event'] = $this->getcolor('users', $result[$c]['user_id'], $user_id, 'event');
                $result[$c]['color_task'] = $this->getcolor('users', $result[$c]['user_id'], $user_id, 'task');
                $admin[3][] = $result[$c]; // SAVE BASIC USERS LIST IN ARRAY.
            }

        }

        // LOGIN USERS EVENT/TASK COLOR.
        $admin['own_event'] = $this->getcolor('own', 0, $user_id, 'event');
        $admin['own_task'] = $this->getcolor('own', 0, $user_id, 'task');
        return $admin;
    }

    public function getCalendar($value_data, $user_id, $start_date, $end_date) {

        $result = array();

        // WHEN THERE IS NO CHECKBOX IS SELECTED THEN RETURN BLANK ARRAY
        $type = gettype($value_data);
        if ($value_data == NULL || $value_data == " ") {
            return $result;
        }

        // WHEN THERE IS ONE OR MORE CHECKBOX IS SELECTED.

        $where = ' WHERE 1=1 ';
        $where .= ' AND (acd_calendar.start_date BETWEEN "' . gmdate("Y-m-d", $start_date) . '" AND "' . gmdate("Y-m-d", $end_date) . '") ';
        
        // THIS FUNCTION IS FOR SET WHERE CONDITION BASED ON USERS CHECKBOX SELECTED.
        $uid = array();
        if ($type == 'array') {
            for ($i = 0; $i < sizeof($value_data); $i++) {
                $users = explode("-", $value_data[$i]);
                if ($users[0] != $uid && $users[1] == 2) {
                    array_push($uid, $users[0]);
                }
                $field = ($users[1] == 1) ? 'acd_calendar.created_by' : 'acd_calendar.assigned_to';

                if ($i == 0) {
                    $where.=' AND (' . $field . ' = ' . $users[0] . ' AND acd_calendar.calendar_type=' . $users[1];
                } else {
                    $where .= ' OR ' . $field . ' = ' . $users[0] . ' AND acd_calendar.calendar_type=' . $users[1];
                }

                if ($i == sizeof($value_data) - 1) {
                    $where .= ')';
                }
            }
        }

        // THIS IS THE QUERY.
        $query = 'SELECT DISTINCT acd_calendar.calendar_id,acd_calendar.created_by,acd_calendar.calendar_for,CASE WHEN duration =1 THEN true ElSE false END as allDay ,acd_calendar.name as title, acd_calendar.calendar_type as calendar_type,acd_calendar.assigned_to, acd_calendar.start_date as  start_date, acd_calendar.end_date as end_date, acd_calendar.due_date,acd_calendar.start_date as utc_strat_date,acd_calendar.end_date as utc_end_date,acd_calendar.status,CONCAT(users.name, " ",users.last_name) as assignedname FROM acd_calendar LEFT JOIN acd_users users on acd_calendar.assigned_to = users.user_id  ' . $where;
        
        $result = $this->_db->rawQuery($query);

        /* SET COLOR FOR EACH EVENTS/TASKS. */
        for ($c = 0; $c < sizeof($result); $c++) {
            if ($result[$c]['calendar_type'] == 1) {
                $result[$c]['start'] = $result[$c]['start_date'];
                $result[$c]['end'] = $result[$c]['end_date'];
                if ($result[$c]['allDay']) {
                    $result[$c]['end'] = date('Y-m-d H:i:s', strtotime($result[$c]['end'] . "+1 days"));
                }
                
            } else {
                $result[$c]['start'] = $result[$c]['start_date'];
                $result[$c]['end'] = $result[$c]['end_date'];
                
            }
            $type = ($result[$c]['calendar_type'] == 1) ? 'created_by' : 'assigned_to';
            if ($user_id == $result[$c][$type]) {
                $c1 = $this->getcolor('own', $result[$c]['created_by'], $user_id, 'event');
                $c2 = $this->getcolor('own', $result[$c]['assigned_to'], $user_id, 'task');
            } else {
                $c1 = $this->getcolor('users', $result[$c]['created_by'], $user_id, 'event');
                if (in_array($result[$c]['assigned_to'], $uid)) {
                    $c2 = $this->getcolor('users', $result[$c]['assigned_to'], $user_id, 'task');
                } else {
                    $c2 = $this->getcolor('users', $result[$c]['created_by'], $user_id, 'task');
                }
            }
            $result[$c]['color'] = ($result[$c]['calendar_type'] == 1) ? $c1 : $c2;
        }


        return $result;
    }

    /* SAVE COLOR SETTINGS. */
     public function saveSetings($data) {
        $insert = json_encode($data['color']);
        $arr = $this->getSetting($data['user_id']);
        if (sizeof($arr) == 0) {
            $array = array("user_id" => $data['user_id'], "value" => $insert, "created_date" => date('Y-m-d H:i:s'));
            $id = $this->_db->insert('acd_calendar_settings', $array);
        } else {
            $array = array("user_id" => $data['user_id'], "value" => $insert, "modified_date" => date('Y-m-d H:i:s'));
            $this->_db->where('user_id', $data['user_id']);
            $id = $this->_db->update('acd_calendar_settings', $array);
        }
        if ($id) {
            $result['error'] = false;
            $result['msg'] = "Successfully Set";
        } else {
            $result['error'] = true;
            $result['msg'] = "error";
        }
        return $result;
    }


}
