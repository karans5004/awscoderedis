<?php header('Content-Type: application/json');
require_once 'vendor/autoload.php';


$db_conn_str_file = '/mnt/efs/credentials.txt';

$file_content = trim(fgets(fopen($db_conn_str_file, 'r')));
$file_content_arr = explode(',', $file_content);

use Aws\SecretsManager\SecretsManagerClient;
use Aws\Exception\AwsException;

$client = new SecretsManagerClient([
	'credentials' => [
        'key'    => $file_content_arr[0],
        'secret' => $file_content_arr[1],
    	],
    'version' => '2017-10-17',
    'region' => 'us-east-1',
]);

try {
    $result = $client->getSecretValue(['SecretId' => $file_content_arr[2] ]);

    if (isset($result['SecretString'])) {
        $secret = $result['SecretString'];
    } else {
        $secret = base64_decode($result['SecretBinary']);
	}

	$db_conn_str = json_decode($secret, true)['connection'];
	
	
	$result = $client->getSecretValue(['SecretId' => $file_content_arr[3] ]);

    if (isset($result['SecretString'])) {
        $secret = $result['SecretString'];
    } else {
        $secret = base64_decode($result['SecretBinary']);
	}

	$cache_conn_str = json_decode($secret, true)['connection'];
	
	
} catch (Exception $e) {
	die(json_encode([
		'error' => true, 
		'message' => $e->getMessage()
	]));
}




$cache_conn_arr = explode(',', $cache_conn_str);


try {
    $redis = new Redis();
    $redis->connect($cache_conn_arr[0], $cache_conn_arr[1]);
    //$redis->auth('password');
    
    if (!$redis->ping()) {
        die(json_encode([
            'error' => true,
            'message' => 'not connected'
        ]));
    }
} catch (Exception $e) {
    die(json_encode([
		'error' => true,
		'message' => $e->getMessage()
	]));
}

$action         = $_GET['operation'];
$db_conn_arr 	= explode(',', $db_conn_str);
$db_url     	= $db_conn_arr[0];
$db_user    	= $db_conn_arr[1];
$db_pass    	= $db_conn_arr[2];
$db_schema	 	= $db_conn_arr[3];
$db_table   	= $db_conn_arr[4];

$db_conn = mysqli_connect($db_url, $db_user, $db_pass, $db_schema);

// check connection
if (!$db_conn) {
    die(json_encode([
        'error' => true,
        'message' => 'Database read connection failed | ' . mysqli_connect_error(),
        'db_conn_str' => $db_conn_str
    ]));
}


// ?action=get_all_db
if ($action === 'get_all_db') {
	$sql = 'SELECT * from ' . $db_table;

	$result = $db_conn->query($sql);

	$products = [];

    while($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }

	die(json_encode([
		'error' => false,
		'products' => $products,
		'source' => 'DB'
	]));

// ?action=get_all_cache
} else if ($action === 'get_all_cache') {
	$cache_keys = $redis->keys('*');
	$products = $redis->mGet($cache_keys);

	die(json_encode([
		'error' => false,
		'products' => array_map(function($product) { return json_decode($product, true); }, $products),
		'source' => 'cache'
	]));


// ?action=search&product_id=1
} else if ($action === 'search') {
    if (!isset($_GET['product_id'])) {
		die(json_encode([
	    	'error' => true,
	    	'message' => 'one or more missing params'
	    ]));
    }
    
    $cache_key = 'PI' . $_GET['product_id'];
    $cache_value = $redis->get($cache_key);
    
    if ($cache_value) {
        die(json_encode([
	    	'error' => false,
            'product' => json_decode($cache_value, true),
            'source' => 'Cache' 
	    ]));
    }

    $sql = 'SELECT * FROM ' . $db_table . ' WHERE id=' . htmlentities($_GET['product_id']) . ' LIMIT 1';

	$result = $db_conn->query($sql);

    if ($result->num_rows == 0) {
        die(json_encode([
	    	'error' => true,
	    	'message' => 'Incorrect product ID'
	    ]));
    }

    $product = mysqli_fetch_assoc($result);

    $redis->set($cache_key, json_encode($product));

    die(json_encode([
        'error' => false,
        'product' => $product,
        'source' => 'DB'
    ]));

// 
} else if ($action === 'add_from_file') {
	$file = fopen($_GET['file_path'], 'r');

	while ($line = fgetcsv($file)) {
		$sql = 'INSERT into ' . $db_table . '(name, quantity, price) values("'. htmlentities($line[0]) . '", "' . htmlentities($line[1]) . '", "' . htmlentities($line[2]) . '")';
		$db_conn->query($sql);
	}

	die(json_encode([
		'error' => true,
		'message' => 'Data added successfully'
	]));

// ?action=add&product_name=soap&product_quantity=5&product_price=45
} elseif ($action === 'add') {
    if (!isset($_GET['product_name']) ||
        !isset($_GET['product_quantity']) ||
        !isset($_GET['product_price'])) {
		die(json_encode([
	    	'error' => true,
	    	'message' => 'one or more missing params'
	    ]));
	}

	$sql = 'INSERT into ' . $db_table . '(name, quantity, price) values("'. htmlentities($_GET['product_name']) . '", "' . htmlentities($_GET['product_quantity']) . '", "' . htmlentities($_GET['product_price']) . '")';

	if ($db_conn->query($sql) === true) {
		die(json_encode([
			'error' => false,
			'message' => 'Data inserted successfully'
		]));
	} else {
		die(json_encode([
			'error' => true,
			'message' => mysqli_error($db_conn)
		]));
	}

// ?action=update&product_id=1&product_name=pant&product_quantity=5&product_price=45
} elseif ($action === 'update') {
	if (!isset($_GET['product_id']) ||
		!isset($_GET['product_name']) ||
		!isset($_GET['product_quantity']) ||
		!isset($_GET['product_price'])) {
		die(json_encode([
	    	'error' => true,
	    	'message' => 'one or more missing params'
	    ]));
	}

	$sql = 'UPDATE ' . $db_table . ' SET name="'. htmlentities($_GET['product_name']) . '", quantity="' . htmlentities($_GET['product_quantity']) . '", price="' . htmlentities($_GET['product_price']) . '" WHERE id = ' . htmlentities($_GET['product_id']);

	if ($db_conn->query($sql) === true) {
		$redis->unlink('PI' . $_GET['product_id']);
		die(json_encode([
			'error' => false,
			'message' => 'Data updated successfully'
		]));
	} else {
		die(json_encode([
			'error' => true,
			'message' => mysqli_error($write_conn)
		]));
	}

// ?action=delete&product_id=1
} elseif ($action === 'delete') {
	if (!isset($_GET['product_id'])) {
		die(json_encode([
	    	'error' => true,
	    	'message' => 'one or more missing params'
	    ]));
	}

    $sql = 'DELETE FROM ' . $db_table . ' WHERE id=' . htmlentities($_GET['product_id']);
    $cache_key = 'PI' . $_GET['product_id'];
    $redis->unlink($cache_key);

	if ($db_conn->query($sql) === true) {
		die(json_encode([
			'error' => false,
			'message' => 'Data deleted successfully'
		]));
	} else {
		die(json_encode([
			'error' => true,
			'message' => mysqli_error($db_conn)
		]));
	}
} else {
	die(json_encode([
		'error' => true,
		'message' => 'invalid/missing action param'
	]));
}