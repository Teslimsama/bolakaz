                        <nav aria-label='Page navigation'>
                            <ul class='pagination justify-content-center mb-3'>
                                <li class='page-item disabled'>
                                    <a class='page-link' href='#' aria-label='Previous'>
                                        <span aria-hidden='true'>&laquo;</span>
                                        <span class='sr-only'>Previous</span>
                                    </a>
                                </li>
                                <li class='page-item active'><a class='page-link' href='#'>1</a></li>
                                <li class='page-item'><a class='page-link' href='#'>2</a></li>
                                <li class='page-item'><a class='page-link' href='#'>3</a></li>
                                <li class='page-item'>
                                    <a class='page-link' href='javascript:load_data(`" .$_POST["query"]."`, ".$next_id." )' aria-label='Next'>
                                        <span aria-hidden='true'>&raquo;</span>
                                        <span class='sr-only'>Next</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        </div>

                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center mb-3">
                                <li class="page-item disabled">
                                    <a class="page-link" href="#" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                        <span class="sr-only">Previous</span>
                                    </a>
                                </li>
                                <?php
                                $conn = $pdo->open();
                                $sql = $conn->prepare("SELECT * FROM products WHERE category_id = :catid");
                                $sql->execute(['catid' => $catid]);
                                $total_rec = $sql->rowCount();
                                $total_pages = ceil($total_rec / $num_pages);

                                for ($i = 1; $i <= $total_pages; $i++) {
                                    foreach ($sql as $row) {
                                        # code...
                                    }
                                    echo "
                       <li class='page-item active'><a class='page-link' href='shop.php?category=" . $slug . "'>" . $i . "</a></li>
                     ";
                                }
                                ?>
                                <!-- <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li> -->
                                <li class="page-item">
                                    <a class="page-link" href="#" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                        <span class="sr-only">Next</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <!-- <?php
                                $conn = $pdo->open();

                                try {
                                    $inc = 3;
                                    $stmt = $conn->prepare("SELECT * FROM products WHERE category_id = :catid LIMIT $startfrom,$num_pages");
                                    $stmt->execute(['catid' => $catid]);
                                    foreach ($stmt as $row) {
                                        $image = (!empty($row['photo'])) ? 'images/' . $row['photo'] : 'images/noimage.jpg';
                                        $inc = ($inc == 3) ? 1 : $inc + 1;
                                        if ($inc == 1) echo "";
                                        echo "
    
                   
                   
                             
                             
                             
                             ";
                                        if ($inc == 3) echo "</div>";
                                    }
                                    if ($inc == 1) echo "<div class='col-sm-4'></div><div class='col-sm-4'></div></div>";
                                    if ($inc == 2) echo "<div class='col-sm-4'></div></div>";
                                } catch (PDOException $e) {
                                    echo "There is some problem in connection: " . $e->getMessage();
                                }

                                ?>
 -->