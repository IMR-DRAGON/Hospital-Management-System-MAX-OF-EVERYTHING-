<?php

function get_doctor_row($connection, $doctor_id) {
    $doctor_id = (int)$doctor_id;
    $result = mysqli_query(
        $connection,
        "SELECT id, first_name, last_name, CONCAT(TRIM(first_name), ' ', TRIM(last_name)) AS full_name
         FROM tbl_employee WHERE id = $doctor_id LIMIT 1"
    );
    return $result ? mysqli_fetch_assoc($result) : null;
}

function get_doctor_patient_ids($connection, $doctor_id) {
    $doctor_id = (int)$doctor_id;
    $patient_ids = [];

    $queries = [
        "SELECT DISTINCT patient_id FROM chat_conversations WHERE doctor_id = $doctor_id AND patient_id IS NOT NULL",
        "SELECT DISTINCT patient_id FROM prescriptions WHERE doctor_id = $doctor_id",
        "SELECT DISTINCT patient_id FROM patient_medical_records WHERE doctor_id = $doctor_id",
        "SELECT DISTINCT patient_id FROM associate_assignments WHERE doctor_id = $doctor_id AND patient_id IS NOT NULL AND assignment_status = 'Active'"
    ];

    foreach ($queries as $sql) {
        $result = mysqli_query($connection, $sql);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $patient_ids[(int)$row['patient_id']] = true;
            }
        }
    }

    $doctor = get_doctor_row($connection, $doctor_id);
    if ($doctor) {
        $doctor_name = mysqli_real_escape_string($connection, trim($doctor['full_name']));
        $doctor_last = mysqli_real_escape_string($connection, trim($doctor['last_name']));
        $appt_q = mysqli_query(
            $connection,
            "SELECT DISTINCT patient_name FROM tbl_appointment
             WHERE TRIM(doctor) = '$doctor_name' OR doctor LIKE '%$doctor_last%'"
        );
        if ($appt_q) {
            while ($appt = mysqli_fetch_assoc($appt_q)) {
                $appt_name = trim(explode(',', $appt['patient_name'])[0]);
                $appt_name_esc = mysqli_real_escape_string($connection, $appt_name);
                $p_q = mysqli_query(
                    $connection,
                    "SELECT id FROM tbl_patient
                     WHERE CONCAT(TRIM(first_name), ' ', TRIM(last_name)) LIKE '%$appt_name_esc%'
                     LIMIT 1"
                );
                if ($p_q && ($p_row = mysqli_fetch_assoc($p_q))) {
                    $patient_ids[(int)$p_row['id']] = true;
                }
            }
        }
    }

    return array_keys($patient_ids);
}

function doctor_has_patient($connection, $doctor_id, $patient_id) {
    return in_array((int)$patient_id, get_doctor_patient_ids($connection, $doctor_id), true);
}

function get_doctor_patients($connection, $doctor_id, $search = '') {
    $patient_ids = get_doctor_patient_ids($connection, $doctor_id);
    if (empty($patient_ids)) {
        return [];
    }

    $id_list = implode(',', array_map('intval', $patient_ids));
    $where = "p.id IN ($id_list)";

    if ($search !== '') {
        $search_esc = mysqli_real_escape_string($connection, trim($search));
        $where .= " AND (
            CONCAT(p.first_name, ' ', p.last_name) LIKE '%$search_esc%'
            OR CAST(p.id AS CHAR) LIKE '%$search_esc%'
            OR p.phone LIKE '%$search_esc%'
        )";
    }

    $result = mysqli_query(
        $connection,
        "SELECT p.id, p.first_name, p.last_name, p.email, p.phone, p.patient_type, p.gender
         FROM tbl_patient p
         WHERE $where
         ORDER BY p.first_name, p.last_name"
    );

    $patients = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $patients[] = $row;
        }
    }

    return $patients;
}

function search_all_patients($connection, $search = '') {
    $where = "status = 1";
    if ($search !== '') {
        $search_esc = mysqli_real_escape_string($connection, trim($search));
        $where .= " AND (
            CONCAT(first_name, ' ', last_name) LIKE '%$search_esc%'
            OR CAST(id AS CHAR) LIKE '%$search_esc%'
            OR phone LIKE '%$search_esc%'
        )";
    }

    $result = mysqli_query(
        $connection,
        "SELECT id, first_name, last_name, email, phone, patient_type, gender
         FROM tbl_patient
         WHERE $where
         ORDER BY first_name, last_name"
    );

    $patients = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $patients[] = $row;
        }
    }

    return $patients;
}
