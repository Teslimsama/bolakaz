<?php

//process.php
include 'session.php';

$conn = $pdo->open();

$slug = $_GET['category'];
try {
    $stmt = $conn->prepare("SELECT * FROM category WHERE cat_slug = ?");
    $stmt->execute([$slug]);
    $cat = $stmt->fetch();
    $catid = $cat['id'];
} catch (PDOException $e) {
    echo "There is some problem in connection: " . $e->getMessage();
}

if(isset($_GET["page"]))
{

	$data = array();

	$limit = 8;

	$page = 1;

	if($_GET["page"] > 1)
	{
		$start = (($_GET["page"] - 1) * $limit);

		$page = $_GET["page"];
	}
	else
	{
		$start = 0;
	}

	$where = '';

	$search_query = '';

	if(isset($_GET["gender_filter"]))
	{
		$where .= ' gender = "'.trim($_GET["gender_filter"]).'" ';

		$search_query .= '&gender_filter='.trim($_GET["gender_filter"]);
	}

	if(isset($_GET["price_filter"]))
	{
		if($where != '')
		{
			$where .= ' AND '. trim($_GET["price_filter"]);
		}
		else
		{
			$where .= trim($_GET["price_filter"]);
		}

		$search_query .= '&price_filter='.trim($_GET["price_filter"]);
	}

	if(isset($_GET["color_filter"]))
	{
		$color_array = explode(",", trim($_GET["color_filter"]));

		if(count($color_array) > 0)
		{
			//if(count($color_array) > 1)
			//{
				if($where != '')
				{
					$color_condition = '';
					foreach($color_array as $color)
					{
						if(trim($color) != '')
						{
							$color_condition .= 'color = "'.trim($color).'" OR ';
						}
					}
					if($color_condition != '')
					{
						$where .= ' AND ('.substr($color_condition, 0, -4).')';
					}
				}
				else
				{
					$color_condition = '';
					foreach($color_array as $color)
					{
						if(trim($color) != '')
						{
							$color_condition .= 'color = "'.trim($color).'" OR ';
						}
					}
					if($color_condition != '')
					{
						$where .= substr($color_condition, 0, -4);
					}
				}
			//}
			$search_query .= '&color_filter=' . trim($_GET["color_filter"]);
		}
	}

	if($where != '')
	{
		$where = 'WHERE ' . $where;
	}

	$query = "
	SELECT name, price, images, color 
	FROM sample_data 
	".$where."
	ORDER BY sample_id ASC
	";

	$filter_query = $query . ' LIMIT ' . $start . ', ' . $limit . '';

	$statement = $conn->prepare($query);

	$statement->execute();

	$total_data = $statement->rowCount();

	$statement = $conn->prepare($filter_query);

	$statement->execute();

	$result = $statement->fetchAll();

	foreach($result as $row)
	{
		// $img_arr = explode(" ~ ", $row['images']);

		$data[] = array(
				'catid'			=>	$row["id"],
				'price'			=>	$row['price'],
				'name'			=>	$row['name'],
				'photo'			=>	$row['photo']
		);

	}

	$pagination_html = '
	<nav aria-label="Page navigation">
  		<ul class="pagination justify-content-center mb-3">
	';

	$total_links = ceil($total_data/$limit);

	$previous_link = '';

	$next_link = '';

	$page_link = '';

	$page_array = '';

	if($total_links > 0)
	{
		if($total_links > 4)
		{
			if($page < 5)
			{
				for($count = 1; $count <= 5; $count++)
				{
					$page_array[] = $count;
				}
				$page_array[] = '...';
				$page_array[] = $total_links;
			}
			else
			{
				$end_limit = $total_links - 5;

				if($page > $end_limit)
				{
					$page_array[] = 1;

					$page_array[] = '...';

					for($count = $end_limit; $count <= $total_links; $count++)
					{
						$page_array[] = $count;
					}
				}
				else
				{
					$page_array[] = 1;

					$page_array[] = '...';

					for($count = $page - 1; $count <= $page + 1; $count++)
					{
						$page_array[] = $count;
					}

					$page_array[] = '...';

					$page_array[] = $total_links;
				}
			}
		}
		else
		{
			for($count = 1; $count <= $total_links; $count++)
			{
				$page_array[] = $count;
			}
		}

		for($count = 0; $count < $count($page_array); $count++)
		{
			if($page == $page_array[$count])
			{
				$page_link .= '
				<li class="page-item active">
		      		<a class="page-link" href="#">'.$page_array[$count].'</a>
		    	</li>
				';

				$previous_id = $page_array[$count] - 1;

				if($previous_id > 0)
				{
					$previous_link = "<li class='page-item'><a class='page-link' href='javascript:load_product(".$previous_id.",`".$search_query."`)' aria-label='Previous'><span aria-hidden='true'>&laquo;</span>
                                        <span class='sr-only'>Previous</span></a></li>";
				}
				else
				{
					$previous_link = "
					<li class='page-item disabled'>
				        <a class='page-link' href='#'> <span aria-hidden='true'>&laquo;</span>
                                        <span class='sr-only'>Previous</span></a>
				    </li>
					";
				}

				$next_id = $page_array[$count] + 1;

				if($next_id >= $total_links)
				{
					$next_link = "
					<li class='page-item disabled'>
		        		<a class='page-link' href='#'><span aria-hidden='true'>&raquo;</span>
                                        <span class='sr-only'>Next</span></a>
		      		</li>
					";
				}
				else
				{
					$next_link = "
					<li class='page-item'><a class='page-link' href='javascript:load_product(".$next_id.",`".$search_query."`)'><span aria-hidden='true'>&raquo;</span>
                                        <span class='sr-only'>Next </span></a></li>
					";
				}

			}
			else
			{
				if($page_array[$count] == '...')
				{
					$page_link .= '
					<li class="page-item disabled">
		          		<a class="page-link" href="#">...</a>
		      		</li>
					';
				}
				else
				{
					$page_link .= '
					<li class="page-item">
						<a class="page-link" href="javascript:load_product('.$page_array[$count].', `'.$search_query.'`)">'.$page_array[$count].'</a>
					</li>
					';
				}
			}
		}

	}

	$pagination_html .= $previous_link . $page_link . $next_link;


	$pagination_html .= '
		</ul>
	</nav>
	';

	$output = array(
		'data'				=>	$data,
		'pagination'		=>	$pagination_html,
		'total_data'		=>	$total_data
	);

	echo json_encode($output);

}


if(isset($_GET["action"]))
{
	$data = array();

	$query = "
	SELECT gender, COUNT(id) AS Total FROM product GROUP BY gender
	";

	foreach($conn->query($query) as $row)
	{
		$sub_data = array();
		$sub_data['name'] = $row['gender'];
		$sub_data['total'] = $row['Total'];
		$data['gender'][] = $sub_data;
	}

	$query = "
	SELECT color, COUNT(id) AS Total FROM product GROUP BY co
	";

	foreach($conn->query($query) as $row)
	{
		$sub_data = array();
		$sub_data['name'] = $row['color'];
		$sub_data['total'] = $row['Total'];
		$data['color'][] = $sub_data;
	}
	$query = "
	SELECT size, COUNT(id) AS Total FROM product GROUP BY size
	";

	foreach($conn->query($query) as $row)
	{
		$sub_data = array();
		$sub_data['name'] = $row['color'];
		$sub_data['total'] = $row['Total'];
		$data['color'][] = $sub_data;
	}

	$price_range = array(
		'price < 1000'						=>	'Under 1000',
		'price > 1000 && price < 5000'		=>	'1000 - 5000', 
		'price > 5000 && price < 10000'		=>	'5000 - 10000',
		'price > 10000 && price < 20000'	=>	'10000 - 20000',
		'price > 20000'						=>	'Over 20000'
	);

	foreach($price_range as $key => $value)
	{
		$query = "
		SELECT COUNT(id) AS Total FROM product 
		WHERE ".$key." 
		";
		$sub_data = array();
		foreach($conn->query($query) as $sub_row)
		{
			$sub_data['name'] = $value;
			$sub_data['total'] = $sub_row['Total'];
			$sub_data['condition'] = $key;		
		}
		$data['price'][] = $sub_data;
	}

	echo json_encode($data);
}
