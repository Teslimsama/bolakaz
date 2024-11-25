<?php
include 'session.php';

$galleryTbl = "products";
$imgTbl     = "gallery_images";

function getRows($conditions = array())
{
    global $conn, $imgTbl, $galleryTbl;

    $sql = 'SELECT *, (SELECT file_name FROM ' . $imgTbl . ' WHERE id = ' . $galleryTbl . '.id ORDER BY id DESC LIMIT 1) as default_image';
    $sql .= ' FROM ' . $galleryTbl;

    if (array_key_exists("where", $conditions)) {
        $sql .= ' WHERE ';
        $whereConditions = [];
        foreach ($conditions['where'] as $key => $value) {
            $whereConditions[] = $key . ' = :' . $key;
        }
        $sql .= implode(' AND ', $whereConditions);
    }

    if (array_key_exists("order_by", $conditions)) {
        $sql .= ' ORDER BY ' . $conditions['order_by'];
    } else {
        $sql .= ' ORDER BY id DESC';
    }

    if (array_key_exists("start", $conditions) && array_key_exists("limit", $conditions)) {
        $sql .= ' LIMIT ' . $conditions['start'] . ',' . $conditions['limit'];
    } elseif (!array_key_exists("start", $conditions) && array_key_exists("limit", $conditions)) {
        $sql .= ' LIMIT ' . $conditions['limit'];
    }

    $stmt = $conn->prepare($sql);
    if (array_key_exists("where", $conditions)) {
        foreach ($conditions['where'] as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
    }

    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (array_key_exists("return_type", $conditions) && $conditions['return_type'] === 'single') {
        foreach ($data as &$row) {
            $imgSql = 'SELECT * FROM ' . $imgTbl . ' WHERE gallery_id = ' . $row['id'];
            $imgStmt = $conn->query($imgSql);
            $images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
            $row['images'] = $images;
        }
        unset($row); // Unset the reference to prevent potential conflicts
    }

    return !empty($data) ? $data : false;
}



function getImgRow($id)
{
    global $conn, $imgTbl;

    $sql = 'SELECT * FROM ' . $imgTbl . ' WHERE id = :id';
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return ($result) ? $result : false;
}


function insertImage($data)
{
    global $conn, $imgTbl, $galleryTbl;

    if (!empty($data) && is_array($data)) {
        $columns = '';
        $values = '';
        $bindings = [];
        if (!array_key_exists('uploaded_on', $data)) {
            $data['uploaded_on'] = date("Y-m-d H:i:s");
        }

        foreach ($data as $key => $val) {
            $pre = !empty($values) ? ', ' : '';
            $columns .= $pre . $key;
            $values .= $pre . ':' . $key;
            $bindings[':' . $key] = $val;
        }

        // Include id as one of the columns to insert
        // $columns .= ', id';
        // $values .= ', :id';
        // Set the id based on the ID you wish
        $bindings[':product_id'] = $data['product_id']; // Replace 'id' with the actual reference for the product ID
        // print_r($bindings);
        $sql = "INSERT INTO $imgTbl ($columns) VALUES ($values)";
        $stmt = $conn->prepare($sql);
        $success = $stmt->execute($bindings);

        return $success ? $conn->lastInsertId() : false;
    } else {
        return false;
    }
}


function idExists($id)
{
    global $conn, $galleryTbl;

    $query = "SELECT COUNT(*) as count FROM $galleryTbl WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $id);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'] > 0; // If count is greater than 0, the ID exists
}


function deleteImage($conditions)
{
    global $conn, $imgTbl, $galleryTbl;

    $whereSql = '';
    if (!empty($conditions) && is_array($conditions)) {
        $whereSql .= ' WHERE ';
        $i = 0;
        foreach ($conditions as $key => $value) {
            $pre = ($i > 0) ? ' AND ' : '';
            $whereSql .= $pre . $key . " = '" . $value . "'";
            $i++;
        }
    }
    $query = "DELETE FROM " . $imgTbl . $whereSql;
    $delete = $conn->query($query);
    return $delete ? true : false;
}

// function convert_filesize($bytes, $decimals = 2)
// {
//     $size = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
//     $factor = floor((strlen($bytes) - 1) / 3);
//     return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
// }