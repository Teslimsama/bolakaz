<?php
$output = "";

// Get gallery data
if (!empty($_POST['id'])) {
    // Include and initialize DB class
    require_once 'image_functions.php';

    $conditions = [
        'where' => ['product_id' => $_POST['id']],
        'return_type' => 'single'
    ];
    $data = getRows($conditions);

    if (!empty($data)) {
        foreach ($data as $row) {
            $output .= '<form method="post" action="image_actions.php" enctype="multipart/form-data">
    <div class="form-group">
        <label>Images:</label>
        <input type="file" name="images[]" class="form-control" multiple>
    </div>';

            if (!empty($row['images'])) {
                foreach ($row['images'] as $image) {
                    $output .= '<div><img style="width: 25%; height:25%" src="../images/' . $image['file_name'] . '" alt="2"><a href="javascript:void(0);" class="badge badge-danger" onclick="deleteImage(' . $image['id'] . ')">delete</a></div>';
                }
            }

            $output .= '<div class="form-group">
        <label>Title:</label>
        <input type="text" name="title" class="form-control" placeholder="Enter title" value="' . $row['title'] . '">
    </div>
    <input type="hidden" name="id" value="' . $row['id'] . '">
    <input type="submit" name="imgSubmit" class="btn btn-success" value="SUBMIT">
</form>';
        }
    } else {
        $output = '<form method="post" action="image_actions.php" enctype="multipart/form-data">
    <div class="form-group">
        <label>Images:</label>
        <input type="file" name="images[]" class="form-control" multiple>
    </div>
    <div class="form-group">
        <label>Title:</label>
        <input type="text" name="title" class="form-control" placeholder="Enter title">
    </div>
    <input type="hidden" name="id" value="' . $_POST['id'] . '">
    <input type="submit" name="imgSubmit" class="btn btn-success" value="SUBMIT">
</form>';
    }
}

echo json_encode($output);
