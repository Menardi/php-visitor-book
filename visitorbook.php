<?php

/*
 * Visitor Book
 * https://github.com/Menardi/php-visitor-book
 */

/*************************************************************************************
 *
 *   Configuration
 *
 *************************************************************************************/

// The number of entries to display on a page at a time
define('ENTRIES_PER_PAGE',       10);

// The name of the SQLite database to store entries in
define('DB_NAME',               'visitorbook.db');

/*
 * If you want to use reCaptcha, put in your public and private keys here. You can get
 * keys for free at https://www.google.com/recaptcha/admin/create. Comment out or remove
 * these lines if you don't want reCaptcha enabled.
 */
//define('RECAPTCHA_PUBLIC_KEY',  '');
//define('RECAPTCHA_PRIVATE_KEY', '');

/*************************************************************************************
 *
 *   End Configuration
 *
 *************************************************************************************/

/*************************************************************************************
 *
 *   Functions
 *
 *************************************************************************************/

/*
 * Function: connectToDB
 * Parameters: None
 * Returns: PDO database connection if successful, false otherwise
 * Description: Connect to the database. If the table for entries doesn't exist, it 
 * will be created.
 */
function connectToDB() {
    try {
        $db = new PDO('sqlite:' . DB_NAME);
        $db->exec('create table if not exists entries (
                    id integer primary key,
                    name text,
                    message text,
                    time integer,
                    ip text)');
    } catch (Exception $e) {
        echo 'Failed to create / open database';
        return false;
    }
    
    return $db;
}

/*
 * Function: addEntry
 * Parameters: PDO_DB $database, string $name, string $message, time $time, string ip
 * Returns: true if entry was added successfully to database, false otherwise
 * Description: Adds an entry to a given database
 */
function addEntry($database, $name, $message, $time, $ip) {

    $query = $database->prepare('insert into entries(name, message, time, ip) values
                                (:name, :message, :time, :ip)');
    $res = $query->execute( array(':name' => $name, ':message' => $message, ':time' => $time, ':ip' => $ip) );
    
    if ($res) {
        return true;
    } else {
        return false;
    }
        
}

/*
 * Function: printEntries
 * Parameters: PDO_DB $database, int $offset
 * Returns: None
 * Description: Prints out ENTRIES_PER_PAGE entries, offset by $offset, with basic HTML formatting.
 *              Also provides Previous and Next links to view all entries.
 */
function printEntries($database, $offset=0) {

    /*
     * Check that the offset is valid, i.e. a non-negative integer, and set to 0 if it isn't
     */
    $offset = filter_var($offset, FILTER_VALIDATE_INT, array('options' => array('default' => 0, 'min_range' => 0) ) );
    
    /*
     * Perform a query on the database to get entries
     */
    $query = $database->prepare('select name, message, time from entries order by id desc limit ' . ENTRIES_PER_PAGE . ' offset :offset');
    $query->execute( array(':offset' => $offset) );
    
    $count = 0;
    
    /*
     * Print out each entry returned from the database one at a time
     */
    while($res = $query->fetch(PDO::FETCH_ASSOC)) {
        echo '<p><strong>' . $res['name'] .'</strong> ' . date(DATE_RFC850, $res['time']) . '<br>';
        echo $res['message'] . "</p>\n";
        $count++;
    }
    
    /*
     * If the count was 0, there were no entries returned from the database, so print a message 
     * explaining this.
     */
    if ($count == 0) {
        
        if ($offset == 0) {
            echo 'No messages.';
        } else {
            echo 'No more messages!';
        }
        
    } else {
    
        // Print 'Previous' link
        if ($offset >= ENTRIES_PER_PAGE) {
            $prevOffset = $offset - ENTRIES_PER_PAGE;
            echo '<a href="?offset=' . ($offset - ENTRIES_PER_PAGE) . '">&lt; Previous</a>';
        }
        
        // Print 'Next' link
        echo '<a href="?offset=' . ($offset + ENTRIES_PER_PAGE) . '">Next &gt;</a>';
    }
    
}

/*************************************************************************************
 *
 *   End Functions
 *
 *************************************************************************************/


// Determine if we should use reCaptcha
if (defined('RECAPTCHA_PUBLIC_KEY') && defined('RECAPTCHA_PRIVATE_KEY')) {
    define('RECAPTCHA_ENABLED', true);
} else {
    define('RECAPTCHA_ENABLED', false);
}

$db = connectToDB();

if(!$db) {
    echo 'Could not connect to db';
}

$nameText = 'Your name';
$messageText = 'Your message';

/*
 * Process an entry if one has been POSTed
 */
if(isset($_POST['postEntry'])) {
    
    // if reCaptcha is enabled, then we must check the result first before posting
    if(RECAPTCHA_ENABLED) {
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://www.google.com/recaptcha/api/verify');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 
                        'privatekey=' . RECAPTCHA_PRIVATE_KEY .
                       '&remoteip=' . $_SERVER['REMOTE_ADDR'] .
                       '&challenge=' . $_POST['recaptcha_challenge_field'] .
                       '&response=' . $_POST['recaptcha_response_field']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        curl_close($ch);
        $recaptchaResult = explode("\n", $res);
    }

    if( isset($recaptchaResult) && $recaptchaResult[0] == 'false' ) {
        echo 'Try the reCaptcha again!';
        $nameText = $_POST['name'];
        $messageText = $_POST['message'];
    } else {
        if (addEntry($db, $_POST['name'], $_POST['message'], time(), $_SERVER['REMOTE_ADDR'])) {
            echo 'Message posted!';
        } else {
            echo 'Something went wrong and your message could not be posted.';
        }
    }
}

printEntries($db, $_GET['offset']);

?>

<form id="newEntryForm" method="post">
    <input type="text" id="name" name="name" maxlength="30" size="30" value="<?php echo $nameText; ?>"></input><br>
    
    <textarea id="message" name="message" rows="5" cols="50"><?php echo $messageText; ?></textarea><br>
    
    <?php if (RECAPTCHA_ENABLED) { ?>
      <script type="text/javascript"
     src="http://www.google.com/recaptcha/api/challenge?k=<?php echo RECAPTCHA_PUBLIC_KEY; ?>">
      </script>
      <noscript>
         <iframe src="http://www.google.com/recaptcha/api/noscript?k=<?php echo RECAPTCHA_PUBLIC_KEY; ?>"
             height="300" width="500" frameborder="0"></iframe><br>
         <textarea name="recaptcha_challenge_field" rows="3" cols="40">
         </textarea>
         <input type="hidden" name="recaptcha_response_field"
             value="manual_challenge">
      </noscript>
    <?php } ?>
    
    <input type="submit" name="postEntry" id="postEntry" value="Post Message"></input>
</form>
