<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Created by Kosala.
 * email: kosala4@gmail.com
 * User: edu
 * Date: 9/27/17
 * Time: 12:42 PM
 */

class Report_data_model extends CI_Model
{
    public function getTotalRecords($table){
        $this->db->select('*');
        $this->db->from($table);
        $query = $this->db->get();
        $res = $query->num_rows();
        return $res;
    }
    public function getSchools(){
        $this->db->select('*');
        $this->db->from('schools');
        $this->db->join('province', 'province.id = schools.province_id', 'left');
        $this->db->join('zone', 'zone.id = schools.zone_id', 'left');
        $query = $this->db->get();

        if ($query->num_rows() >= 1) {
            $res  = $query->result_array();
            return $res;
        } else{
            return 0;
        }
    }
    
    public function getFundsBalance($school_id){
        $this->db->select('amount');
        $this->db->where('school_id', $school_id);
        $query1 = $this->db->get('funds');
        
        $totalFunds = 0;

        foreach ($query1->result() as $row){
            $totalFunds += $row->amount;
        }

        $this->db->select('amount');
        $this->db->where('school_id', $school_id);
        $query2 = $this->db->get('expenses');

        $totalExpenses = 0;

        foreach ($query2->result() as $row){
            $totalExpenses += $row->amount;
        }
        
        $balance = $totalFunds - $totalExpenses;
        return $balance;
    }
    
    public function getTotalStudents($school_id){
        $res = ['male' => 0, 'female' => 0];
        $status_active = array('Phase 1', 'Phase 2', 'Phase 3');
        
        $this->db->select('id, gender, status');
        $this->db->where('school_id', $school_id);
        $this->db->where_in('status', $status_active);
        $query = $this->db->get('students_info');
        $res['total'] = $query->num_rows();
        
        if ($query->num_rows() > 0) {
            foreach ($query->result() as $row){
                if ($row->gender == 'Male'){
                    $res['male'] ++;
                } else {
                    $res['female'] ++;
                }
            }
        }
            
        
        return $res;
    }

    public function getSchoolTeachers($school_id, $r_type){
        $this->db->select('t.createdTime, title AS Title, teacher_in_name AS Name With Initials, s1.subject_name AS Subject 01, s2.subject_name AS Subject 02, s3.subject_name AS Subject 03');
        $this->db->join('subject_list s1', 's1.id = t.teacher_sub_1', 'left');
        $this->db->join('subject_list s2', 's2.id = t.teacher_sub_2', 'left');
        $this->db->join('subject_list s3', 's3.id = t.teacher_sub_3', 'left');
        $this->db->where('school_id', $school_id);
        $this->db->order_by('t.createdTime', 'DESC');
        $query = $this->db->get('teachers t');

        $row = $query->row();

        if($r_type == 'list'){
            return $query->result_array();
        }else if($r_type == 'count'){
            $res['count'] = $query->num_rows();
            $res['last_update'] = $query->row()->createdTime;
            return $res;
        }
    }
    
    public function getSchoolClasses($school_id, $r_type){
        $this->db->select('c.timeCreated, grade AS Grade, class_name AS Class Name, commenced_date AS Commenced Date, t.teacher_in_name AS Teacher');
        $this->db->join('teachers t', 't.id = c.class_teacher', 'left');
        $this->db->where('c.school_id', $school_id);
        $query = $this->db->get('classes c');

        if($r_type == 'list'){
            return $query->result_array();
        }else if($r_type == 'count'){
            $res['count'] = $query->num_rows();
            $res['last_update'] = $query->row()->timeCreated;
            return $res;
        }
    }
    
    public function getSchoolFunds($school_id, $r_type){
        $this->db->select('f.timeCreated, fl.fund_name AS Teacher, fund_purpose AS Fund Purpose, amount AS Amount, received_date AS Received Date');
        $this->db->join('funds_list fl', 'fl.id = f.fund_id', 'left');
        $this->db->where('f.school_id', $school_id);
        $query = $this->db->get('funds f');

        if($r_type == 'list'){
            return $query->result_array();
        }else if($r_type == 'total'){
            $res['total'] = 0;
            if ($query->num_rows() > 0) {
                foreach ($query->result() as $row){
                    $res['total'] += $row->Amount;
                }
            }
            $res['last_update'] = $query->row()->timeCreated;
            return $res;
        }
    }
    
    public function getSchoolStudents($school_id, $r_type, $filter){
        $this->db->select('timeCreated, index_no AS Index No, UPPER(nic) AS NIC, in_name AS Name With Initials, gender AS Gender');
        $this->db->where('school_id', $school_id);

        if ($filter != "all") {
            $this->db->where('gender', $filter);
        }
        

        $query = $this->db->get('students_info');
        $res = ['male' => 0, 'female' => 0];

        if($r_type == 'list'){
            return $query->result_array();
        }else if($r_type == 'count'){
            $res['count'] = $query->num_rows();
            $res['last_update'] = $query->row()->timeCreated;

            if ($query->num_rows() > 0) {
                foreach ($query->result() as $row){
                    if ($row->Gender == 'Male'){
                        $res['male'] ++;
                    } else {
                        $res['female'] ++;
                    }
                }
            }
            return $res;
        }
    }
    
    public function getSubjectTeachers($subject_id, $r_type){
        $this->db->select('t.createdTime, t.school_id AS School ID, t.title AS Title, t.teacher_in_name AS Name With Initials, s.schoolname AS School Name');
        $this->db->where('teacher_sub_1', $subject_id);
        $this->db->or_where('teacher_sub_2', $subject_id);
        $this->db->or_where('teacher_sub_3', $subject_id);
        $this->db->join('schools s', 's.census_id = t.school_id');
        $this->db->order_by('t.school_id', 'ASC');
        $query = $this->db->get('teachers t');

        if($r_type == 'list'){
            return $query->result_array();
        }else if($r_type == 'count'){
            $res['count'] = $query->num_rows();
            $res['last_update'] = $query->row()->createdTime;
            return $res;
        }
    }
    
    public function getSubjectClasses($subject_id, $r_type){
        $this->db->select('c.timeCreated, s.census_id AS School ID, c.grade AS Grade, c.class_name AS Class Name, c.commenced_date AS Commenced Date, t.teacher_in_name AS Teacher');
        $this->db->where('cs.subject_id', $subject_id);
        $this->db->join('classes c', 'c.id = cs.class_id');
        $this->db->join('schools s', 's.census_id = cs.school_id');
        $this->db->join('teachers t', 't.id = cs.teacher_id');
        $this->db->order_by('s.census_id', 'ASC');
        $query = $this->db->get('class_subjects cs');

        if($r_type == 'list'){
            return $query->result_array();
        }else if($r_type == 'count'){
            $res['count'] = $query->num_rows();
            $res['last_update'] = $query->row()->timeCreated;
            return $res;
        }
    }
    
    public function getSubjectStudents($subject_id, $r_type){
        $this->db->select('si.timeCreated, s.census_id AS School ID, si.index_no AS Index No, UPPER(si.nic) AS NIC, si.in_name AS Name With Initials, si.gender AS Gender');
        $this->db->where('ss.subject_id', $subject_id);
        $this->db->join('schools s', 's.census_id = ss.school_id');
        $this->db->join('students_info si', 'si.id = ss.student_id');
        $this->db->order_by('s.census_id', 'ASC');
        $query = $this->db->get('student_subjects ss');
        $res = ['male' => 0, 'female' => 0];

        if($r_type == 'list'){
            return $query->result_array();
        }else if($r_type == 'count'){
            $res['count'] = $query->num_rows();
            $res['last_update'] = $query->row()->timeCreated;

            if ($query->num_rows() > 0) {
                foreach ($query->result() as $row){
                    if ($row->gender == 'Male'){
                        $res['male'] ++;
                    } else {
                        $res['female'] ++;
                    }
                }
            }
            
            return $res;
        }
    }
}