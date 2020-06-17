<?php



$item = 52315;

$item = 13719;

$debug = true;

if (isset($_GET['item']))
{
	$item = $_GET['item'];
}

//print_r($_REQUEST);


//----------------------------------------------------------------------------------------
function get($url)
{
	$data = null;
	
	$opts = array(
	  CURLOPT_URL =>$url,
	  CURLOPT_FOLLOWLOCATION => TRUE,
	  CURLOPT_RETURNTRANSFER => TRUE,
	  CURLOPT_SSL_VERIFYHOST => FALSE,
	  CURLOPT_SSL_VERIFYPEER => FALSE,
	);
	
	$ch = curl_init();
	curl_setopt_array($ch, $opts);
	$data = curl_exec($ch);
	$info = curl_getinfo($ch); 
		
	//print_r($info);	
	
	curl_close($ch);
	
	return $data;
}

//----------------------------------------------------------------------------------------



// debug

// item

if ($debug)
{
	$filename = $item . '.json';
	
	if (!file_exists($filename))
	{
		$url = 'https://www.biodiversitylibrary.org/api2/httpquery.ashx?op=GetItemMetadata&itemid=' 
			. $item . '&ocr=f&pages=t&apikey=' . '0d4f0303-712e-49e0-92c5-2113a5959159' . '&format=json';
			
		$json = get($url);
			
		file_put_contents($filename, $json);
	}
	
	$json = file_get_contents($filename);

}
else
{
	$url = 'https://www.biodiversitylibrary.org/api2/httpquery.ashx?op=GetItemMetadata&itemid=' 
		. $item . '&ocr=t&pages=t&apikey=' . '0d4f0303-712e-49e0-92c5-2113a5959159' . '&format=json';

	$json = get($url);
}


$item_data = json_decode($json);


// articles		


if ($debug)
{
	$filename = $item . '-articles.json';
	
	if (!file_exists($filename))
	{
		$url = 'http://direct.biostor.org/itemarticles.php?item=' . $item;

		$json = get($url);	
		
		file_put_contents($filename, $json);
	}
	
	$json = file_get_contents($filename);

}
else
{
	$url = 'http://direct.biostor.org/itemarticles.php?item=' . $item;

	$json = get($url);	
}



$item_articles = json_decode($json);

//print_r($item_articles);
//exit();



$article_to_row = array();
$page_to_biostor = array();

$biostor_to_article = array();





// articles
if (isset($item_articles->articles))
{
	foreach ($item_articles->articles as $article)
	{	
		foreach ($article->bhl_pages as $PageID)
		{
			if (!isset($page_to_biostor[$PageID]))
			{
				$page_to_biostor[$PageID][] = $article->reference_id;
				
				$biostor_to_article[$article->reference_id] = $article;
			}
		}	
	}
}



$current_row = 0;
$rows = array();

$page_counter = 0;

$biostor_to_row = array();

$biostor_to_row[$biostor] = $current_row;	

$page_labels = array();	


// 1 = before article
// 2 = in article

$state = 1;

// iterate over pages
foreach ($item_data->Result->Pages as $page)
{
	print_r($page);
	
	$label = '';

	if (isset($page->PageNumbers))
	{
		$label = $page->PageNumbers[0]->Number;
		$label = str_replace('%',' ',$label);
		if ($label != '')
		{
			$label .= ', ';
		}
		
	}
	$label .= $page->PageID;
	$page_labels[$page->PageID] = $label;


	// is page in an article?
	
	if (isset($page_to_biostor[$page->PageID]))
	{
		$state = 2;
	
		foreach ($page_to_biostor[$page->PageID] as $biostor)
		{
			if (!isset($biostor_to_row[$biostor]))
			{
				$current_row++;
				$biostor_to_row[$biostor] = $current_row;
				
				$row_obj = new stdclass;
				$row_obj->pages = array();
				$row_obj->biostor = $biostor;
				
				$row_obj->part = $biostor_to_article[$biostor];
				
				$row[$current_row] = $row_obj;
				
			}			
		}
		
		foreach ($page_to_biostor[$page->PageID] as $biostor)
		{
			$row_index = $biostor_to_row[$biostor];
			$row[$row_index]->pages[] = $page->PageID;
		}
	}
	else
	{
		if ($state == 2)
		{
				$current_row++;
				
				$row_obj = new stdclass;
				$row_obj->pages = array();
				
				
				$row[$current_row] = $row_obj;
				
				$state = 1;			
		}
		$row_index = $current_row;
		
		$row[$row_index]->pages[] = $page->PageID;
	
	}

	
}

// fix

$n = count($row);
for ($i = 0; $i < $n; $i++)
{
	if (isset($row[$i]->biostor))
	{
		$row[$i]->pages = array_values($row[$i]->part->bhl_pages);
	}
}


//print_r($row);

//file_put_contents('x.txt', print_r($row, true));


// dump html

$html = '<html>';	
$html .= '<head>';	
$html .= '</head>';	

$html .= '<body>';	

$html .= '<table>';


$columns = 60;

$keys = array('reference_id', 'title', 'authors', 'secondary_title', 'volume', 'spage', 'epage', 'year', 'doi');		

foreach ($row as $r)
{
	$r1 = array();
	$r2 = array();

	for ($i = 0; $i < $columns; $i++)
	{
		$r1[$i] = '';
		$r2[$i] = '';
	}
	
	$count = 0;

	if (isset($r->biostor))
	{
		foreach ($keys as $k)
		{
			if (isset($r->part->{$k}))
			{
				switch ($k)
				{
					case 'authors':
						$a = array();
						foreach ($r->part->{$k} as $author)
						{
							$a[] = $author->forename . ' ' . $author->lastname;
						}
					
						$r1[$count++] = join(';', $a);
						break;
					
					default:
						$r1[$count++] = $r->part->{$k};
						break;				
				}
			
			}
		
		}
	}
	
	$start = count($keys);
		
	foreach ($r->pages as $PageID)
	{
		// page image
		$r2[$start] = '<img src="http://exeg5le.cloudimg.io/s/height/200/http://biodiversitylibrary.org/pagethumb/' . $PageID . ',200,200">';
		// page name
		$r1[$start] = $page_labels[$PageID];
		
		$start++;
	}
	$html .= '<tr>';
	$html .= '<td>';
	
	$html .=  join("</td><td>", $r1) . "\n";

	$html .= '</td>';
	$html .= '</tr>';


	$html .= '<tr>';
	$html .= '<td>';

	$html .=  join("</td><td>", $r2) . "\n";
	
	$html .= '</td>';
	$html .= '</tr>';


}
$html .= '</table>';
$html .= '</body></html>';


file_put_contents($item  . '.html', $html);


// dump tsv
$tsv = '';

$columns = 60;

$keys = array('reference_id', 'title', 'authors', 'secondary_title', 'volume', 'spage', 'epage', 'year', 'doi');

// header
$header = array();
for ($i = 0; $i < $columns; $i++)
{
	$header[$i] = '';
}
$n = count($keys);
for ($i = 0; $i < $n; $i++)
{
	$header[$i] = $keys[$i];
}
$tsv .=  join("\t", $header) . "\n";


foreach ($row as $r)
{
	$r1 = array();
	$r2 = array();

	for ($i = 0; $i < $columns; $i++)
	{
		$r1[$i] = '';
		$r2[$i] = '';
	}
		
	$count = 0;

	if (isset($r->biostor))
	{
		foreach ($keys as $k)
		{
			if (isset($r->part->{$k}))
			{
				switch ($k)
				{
					case 'authors':
						$a = array();
						foreach ($r->part->{$k} as $author)
						{
							$a[] = $author->forename . ' ' . $author->lastname;
						}
					
						$r1[$count++] = join(';', $a);
						break;
					
					default:
						$r1[$count++] = $r->part->{$k};
						break;				
				}
			
			}
		
		}
	}
	
	$start = count($keys);
		
	foreach ($r->pages as $PageID)
	{
		// page image
		$r2[$start] = '=IMAGE("http://exeg5le.cloudimg.io/s/height/200/http://biodiversitylibrary.org/pagethumb/' . $PageID . ',200,200",1)';
		// page name
		$r1[$start] = $page_labels[$PageID];
		
		$start++;
	}
	
	$tsv .=  join("\t", $r1) . "\n";

	$tsv .=  join("\t", $r2) . "\n";

}

file_put_contents($item . '.tsv', $tsv);


// display

/*
$html = '<html>';	
$html .= '<head>';	
$html .= '<link rel="stylesheet" type="text/css" href="css/materialize.min.css">';
$html .= '</head>';	

$html .= '<body>';	

$html .= '<div style="background:#EEE;display: block;overflow: auto;">';	

foreach ($item_data->Result->Pages as $page)
{
	$html .=  '<a ';
	$html .=  'style="background-color:' . $page_colours[$page->PageID] . ';padding:10px;margin:0px;float:left;width:auto;height:auto;"';
	
	
	if ($page_colours[$page->PageID] == $colour_no_article )
	{
		$html .= ' href="https://biodiversitylibrary.org/page/' . $page->PageID . '"';
	}
	else
	{
		$html .= ' href="https://biostor.org/reference/' . $page_to_biostor[$page->PageID] . '"';		
	}
	$html .= ' target="_new"';
	$html .= '>';
	
	
	$html .= '<img style="border:1px solid rgb(192,192,192);" height="130" src="http://exeg5le.cloudimg.io/s/height/200/http://biodiversitylibrary.org/pagethumb/' . $page->PageID . ',200,200" />';
	
	if (isset($page->PageNumbers))
	{
		$html .=  '<div style="text-align:center">' . $page->PageNumbers[0]->Prefix . '&nbsp;' . str_replace('%', '&nbsp;', $page->PageNumbers[0]->Number) . '</div>';
	}
	else
	{
		$html .=  '<div style="text-align:center">' . $page->PageID . '</div>';
	}
	$html .=  '</a>';
}	

$html .= '</div>';		

$html .= '</body>';	
$html .= '</html>';	

echo $html;
*/

?>