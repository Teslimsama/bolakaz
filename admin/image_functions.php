<?php
include 'session.php';

$galleryTbl = "gallery";
$imgTbl     = "gallery_images";

function getRows($conditions = array())
{
    global $conn, $imgTbl, $galleryTbl;

    $sql = 'SELECT *, (SELECT file_name FROM ' . $imgTbl . ' WHERE product_id = ' . $galleryTbl . '.product_id ORDER BY id DESC LIMIT 1) as default_image';
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


/* 
     * Insert data into the database 
     * @param string name of the table 
     * @param array the data for inserting into the table 
     */
function insert($data)
{
    global $conn, $imgTbl, $galleryTbl;

    if (!empty($data) && is_array($data)) {
        $columns = '';
        $values = '';
        $bindings = [];

        if (!array_key_exists('created', $data)) {
            $data['created'] = date("Y-m-d H:i:s");
        }

        if (!array_key_exists('modified', $data)) {
            $data['modified'] = date("Y-m-d H:i:s");
        }

        foreach ($data as $key => $val) {
            $pre = !empty($values) ? ', ' : '';
            $columns .= $pre . $key;
            $values .= $pre . ':' . $key;
            $bindings[':' . $key] = $val;
        }

        // Include product_id as one of the columns to insert
        // $columns .= ', product_id';
        // $values .= ', :product_id';
        print_r($data);
        // Set the product_id based on the ID in the $data array
        $bindings[':product_id'] = $data['product_id']; // Replace 'id' with the actual reference for the product ID
        print_r($bindings);

        // Prepare the SQL query
        $sql = "INSERT INTO $galleryTbl ($columns) VALUES ($values)";
        $stmt = $conn->prepare($sql);

        // Execute the prepared statement with the bindings
        $success = $stmt->execute($bindings);

        // Return the last inserted ID if successful, otherwise, return false
        return $success ? $conn->lastInsertId() : false;
    } else {
        return false;
    }
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

        // Include product_id as one of the columns to insert
        // $columns .= ', product_id';
        // $values .= ', :product_id';
        // Set the product_id based on the ID you wish
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



/* 
     * Update data into the database 
     * @param string name of the table 
     * @param array the data for updating into the table 
     * @param array where condition on updating data 
     */
function update($data, $conditions)
{
    global $conn, $galleryTbl;

    if (!empty($data) && is_array($data)) {
        $colvalSet = '';
        $params = [];

        if (!array_key_exists('modified', $data)) {
            $data['modified'] = date("Y-m-d H:i:s");
        }

        foreach ($data as $key => $val) {
            $pre = !empty($colvalSet) ? ', ' : '';
            $colvalSet .= $pre . $key . ' = :' . $key;
            $params[':' . $key] = $val;
        }

        $whereSql = '';
        if (!empty($conditions) && is_array($conditions)) {
            $i = 0;
            foreach ($conditions as $key => $value) {
                $pre = ($i > 0) ? ' AND ' : '';
                $whereSql .= $pre . $key . ' = :' . $key;
                $params[':' . $key] = $value;
                $i++;
            }
            $whereSql = ' WHERE ' . $whereSql;
        }

        // Ensure the product_id is set based on the id
        $colvalSet .= ', product_id = :product_id';
        $params[':product_id'] = $conditions['id']; // Assuming 'id' in $conditions refers to the ID you want to set as the product_id
        // print_r($params);

        $query = "UPDATE $galleryTbl SET $colvalSet $whereSql";
        $stmt = $conn->prepare($query);
        $success = $stmt->execute($params);

        return $success ? $stmt->rowCount() > 0 : false;
    } else {
        return false;
    }
}


function idExists($id)
{
    global $conn, $galleryTbl;

    $query = "SELECT COUNT(*) as count FROM $galleryTbl WHERE product_id = :product_id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':product_id', $id);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'] > 0; // If count is greater than 0, the ID exists
}


/* 
     * Delete data from the database 
     * @param string name of the table 
     * @param array where condition on deleting data 
     */

function delete($conditions)
{
    global $conn, $galleryTbl;

    $whereConditions = [];
    $params = [];

    if (!empty($conditions) && is_array($conditions)) {
        foreach ($conditions as $key => $value) {
            $whereConditions[] = $key . ' = :' . $key;
            $params[':' . $key] = $value;
        }
    }

    $whereSql = (!empty($whereConditions)) ? ' WHERE ' . implode(' AND ', $whereConditions) : '';

    $query = "DELETE FROM $galleryTbl $whereSql";
    $stmt = $conn->prepare($query);

    $success = $stmt->execute($params);
    return $success ? true : false;
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
function compressImage($source, $destination, $quality)
{
    // Get image info
    $imgInfo = getimagesize($source);
    $mime = $imgInfo['mime'];

    // Create a new image from file
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source);
            break;
        default:
            $image = imagecreatefromjpeg($source);
    }

    // Correct image orientation
    $image = correctImageOrientation($image, $source, $mime);

    // Save image
    imagejpeg($image, $destination, $quality);

    // Free up memory
    imagedestroy($image);

    // Return compressed image path
    return $destination;
}

function correctImageOrientation($image, $filename, $mime)
{
    if ($mime == 'image/jpeg') {
        if (!is_resource($image)) {
            $image = imagecreatefromjpeg($filename);
        }

        $exif = exif_read_data($filename);
        if (!empty($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 3:
                    $image = imagerotate($image, 180, 0);
                    break;
                case 6:
                    $image = imagerotate($image, -90, 0);
                    break;
                case 8:
                    $image = imagerotate($image, 90, 0);
                    break;
            }

            return $image;
        }
    }
    return false;
}


function convert_filesize($bytes, $decimals = 2)
{
    $size = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
}