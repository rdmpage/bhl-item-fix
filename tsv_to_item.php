<?php

ini_set("auto_detect_line_endings", true); // vital because some files have Windows ending


//----------------------------------------------------------------------------------------

$filename = '52315.tsv';
$filename = 'BHL Item template - Sheet1.tsv';
$filename = 'y.tsv';
$filename = '52315-edited.txt';

$headings = array();

$row_count = 0;

$file = @fopen($filename, "r") or die("couldn't open $filename");
		
$file_handle = fopen($filename, "r");
while (!feof($file_handle)) 
{
	$line = trim(fgets($file_handle));
	
	//echo $line . "|\n";
	
	$row = explode("\t", $line);
		
	$go = is_array($row);
	
	if ($go)
	{
		if ($row_count == 0)
		{
			$headings = $row;		
		}
		else
		{
			//print_r($row);
			
			
			
			$obj = new stdclass;
			
			$obj->images = array();
			
			$col_counter = 0;
		
			foreach ($row as $k => $v)
			{
				// echo $v . "\n";
			
				if ($v != '')
				{
					if (isset($headings[$k]) && $headings[$k] != '')
					{
						$obj->{$headings[$k]} = $v;
					}
					
					if ($col_counter > 8 && preg_match('/(?<page>\d+)$/', $v, $m))
					{
						$obj->images[] = $m['page'];
					}
					
				}
				
				$col_counter++;
			}
		
			// print_r($obj);	
			
			// process
			
			if (isset($obj->reference_id) && is_numeric($obj->reference_id) && count($obj->images) > 0)
			{
				echo "-- " . $obj->title . "\n";
				$sql = '';
				$sql .= 'DELETE FROM rdmp_reference_page_joiner WHERE referenced_id=' . $obj->reference_id . ';' . "\n";
				$sql .= 'UPDATE rdmp_reference SET PageID=' . $obj->images[0] . ' WHERE reference_id=' . $obj->reference_id . ';' . "\n";
				
				$counter = 0;
				foreach ($obj->images as $image)
				{
					$sql .= 'INSERT INTO rdmp_reference_page_joiner(reference_id, PageID, page_order) VALUES (' . $obj->reference_id . ',' . $image . ',' . $counter . ');' . "\n";
					$counter++;
				}
				
				echo $sql . "\n";
			}
			
			
			
		}
	}	
	$row_count++;
}
?>

