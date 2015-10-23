<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class DBConfig
{
    public static $DB_CONNSTRING;
    public static $DB_USERNAME;
    public static $DB_PASSWORD;
    public static $DB_ADMIN_EMAIL;

    public function __construct()
    {
        self::$DB_CONNSTRING = "mysql:host=localhost;dbname=webticket";
        self::$DB_USERNAME = "webticket";
        self::$DB_PASSWORD = "webticket";
    }
}

new DBConfig();

class Ticket
{
    private static $idList = array();
    private $id;
    private $timestamp;
    private $submitter;
    private $weburl;
    private $description;
    private $fileurl;
    private $status;
    private $assigned;
    private $completed;

    public function __construct($id, $timestamp, $submitter, $weburl, $description, $fileurl, $status, $assigned, $completed)
    {
        $this->id = $id;
        $this->timestamp = $timestamp;
        $this->submitter = $submitter;
        $this->weburl = $weburl;
        $this->description = $description;
        $this->fileurl = $fileurl;
        $this->status = $status;
        $this->assigned = $assigned;
        $this->completed = $completed;
    }

    public static function create($id, $timestamp, $submitter, $weburl, $description, $fileurl, $status, $assigned, $completed)
    {
        if (!isset(self::$idList[$id])) {
            self::$idList[$id] = new Ticket($id, $timestamp, $submitter, $weburl, $description, $fileurl, $status, $assigned, $completed);
        }
        return self::$idList[$id];
    }

    public function getID()
    {
        return $this->id;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function getSubmitter()
    {
        return $this->submitter;
    }

    public function getWebURL()
    {
        return $this->weburl;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getFileURL()
    {
        return $this->fileurl;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getAssigned()
    {
        return $this->assigned;
    }

    public function getCompleted()
    {
        return $this->completed;
    }

    public function setAssigned($assigned)
    {
        $this->assigned = $assigned;
    }

    public function setCompleted($completed)
    {
        $this->completed = $completed;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }
}

class TicketDAO
{
    public function getAllTickets()
    {
        $sqlQuery = "SELECT * FROM tickets ORDER BY timestamp DESC;";
        $dbh = new PDO(DBconfig::$DB_CONNSTRING, DBConfig::$DB_USERNAME, DBConfig::$DB_PASSWORD);
        $queryResultSet = $dbh->query($sqlQuery);
        $ticketResults = $queryResultSet->fetchAll(PDO::FETCH_ASSOC);
        $ticketArray = array();
        foreach ($ticketResults as $row) {
            $newTicket = new Ticket($row["id"], $row["timestamp"], $row["submitter"], $row["weburl"], $row["description"], $row["fileurl"], $row["status"], $row["assigned"], $row["completed"]);
            $ticketArray[] = $newTicket;
        }
        return $ticketArray;
    }

    public function getTicket($id)
    {
        $sqlQuery = "SELECT * FROM tickets WHERE id =:id";
        $dbh = new PDO(DBconfig::$DB_CONNSTRING, DBConfig::$DB_USERNAME, DBConfig::$DB_PASSWORD);
        $stmt = $dbh->prepare($sqlQuery);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $ticketData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $ticket = new Ticket($ticketData[0]["id"], $ticketData[0]["timestamp"], $ticketData[0]["submitter"], $ticketData[0]["weburl"], $ticketData[0]["description"], $ticketData[0]["fileurl"], $ticketData[0]["status"], $ticketData[0]["assigned"], $ticketData[0]["completed"]);
        return $ticket;
    }


    public function insertNewTicket($ticket)
    {
        /** @var Ticket $ticket */
        $sqlInsert = "INSERT INTO tickets VALUES (NULL, NULL, :submitter, :weburl, :description, :fileurl, 1, NULL, NULL);";
        $dbh = new PDO(DBconfig::$DB_CONNSTRING, DBConfig::$DB_USERNAME, DBConfig::$DB_PASSWORD);
        $stmt = $dbh->prepare($sqlInsert);
        $stmt->bindParam(':submitter', $ticket->getSubmitter());
        $stmt->bindParam(':weburl', $ticket->getWebURL());
        $stmt->bindParam(':description', $ticket->getDescription());
        $stmt->bindParam(':fileurl', $ticket->getFileURL());
        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    }

    public function updateTicket($ticket)
    {
        /** @var Ticket $ticket */
        $sqlInsert = "UPDATE tickets SET
                        status =:status,
                        assigned =:assigned,
                        completed = :completed
                        WHERE id = :id;";
        $dbh = new PDO(DBconfig::$DB_CONNSTRING, DBConfig::$DB_USERNAME, DBConfig::$DB_PASSWORD);
        $stmt = $dbh->prepare($sqlInsert);
        $stmt->bindParam(':status', $ticket->getStatus());
        $stmt->bindParam(':assigned', $ticket->getAssigned());
        $stmt->bindParam(':completed', $ticket->getCompleted());
        $stmt->bindParam(':id', $ticket->getID());
        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    }
}

$ticketDAO = new TicketDAO();
if (isset($_POST["submitter"]) && isset($_POST["weburl"]) && isset($_POST["description"])) {

    if (!empty($_POST["submitter"]) && !empty($_POST["weburl"]) && !empty($_POST["description"])) {
        $ticket = new Ticket(null, null, $_POST["submitter"], $_POST["weburl"], $_POST["description"], $_POST["fileurl"], 1, null, null);
        $ticketDAO->insertNewTicket($ticket);
        $_POST["description"] = "";
    } else {
        print "Something wasn't filled";
    }
}

if (isset($_POST["id"]) && !empty($_POST["id"])) {
    $ticket = $ticketDAO->getTicket($_POST["id"]);
    if (isset($_POST["status"]) && !empty($_POST["status"])) {
        $ticket->setStatus($_POST["status"]);
    }
    if (isset($_POST["assigned"]) && !empty($_POST["assigned"])) {
        $ticket->setAssigned($_POST["assigned"]);
    }
    if (isset($_POST["completed"]) && !empty($_POST["completed"])) {
        $timestamp = date('Y-m-d G:i:s');
        $ticket->setCompleted($timestamp);
    }
    $ticketDAO->updateTicket($ticket);
    var_dump("Admin Done");
}

$ticketContainer = $ticketDAO->getAllTickets();
?>
<link rel="stylesheet" href="css/bootstrap.css" type="text/css"/>
<h1>Website Ticketing System (For iKR tracking)</h1>
<div style="padding-left: 30px;">
    <form action="index.php" method="post">
        <input type="text" name="submitter" placeholder="Submitter"
               value="<?php isset($_POST["submitter"]) ? print $_POST["submitter"] : print ""; ?>">
        <input type="text" size="40" name="weburl" placeholder="Website URL"
               value="<?php isset($_POST["weburl"]) ? print $_POST["weburl"] : print ""; ?>"><br><br>
        <textarea cols="40" rows="3" name="description"
                  placeholder="Description"><?php isset($_POST["description"]) ? print $_POST["description"] : print ""; ?></textarea><br><br>
        <input type="text" size="40" name="fileurl" placeholder="File/Folder URL (Dropbox or Drive) IF ANY"
               value="<?php isset($_POST["fileurl"]) ? print $_POST["fileurl"] : print ""; ?>">
        <input type="submit" class="btn-xs btn-info ">
    </form>
</div>
<table class="table table-hover">
    <thead>
    <th style="width: 5%;text-align: center">
        ID
    </th>
    <th style="width: 10%;text-align: center">
        Timestamp
    </th>
    <th style="width: 10%;text-align: center">
        Submitter
    </th>
    <th style="width: 5%;text-align: center">
        WebURL
    </th>
    <th style="width: 40%;text-align: center">
        Description
    </th>
    <th style="width: 10%;text-align: center">
        FileURL
    </th>
    <th style="width: 5%;text-align: center">
        Status
    </th>
    <th style="width: 5%;text-align: center">
        Assigned
    </th>
    <th style="width: 10%;text-align: center">
        Completed
    </th>
    </thead>
    <tbody>
    <?php foreach ($ticketContainer as $ticket) :
        /** @var Ticket $ticket */
        ?>
        <tr>
            <td style="text-align: center">
                <?php print htmlspecialchars($ticket->getID()); ?>
            </td>
            <td style="text-align: center">
                <?php print htmlspecialchars($ticket->getTimestamp()); ?>
            </td>
            <td style="text-align: center">
                <?php print htmlspecialchars($ticket->getSubmitter()); ?>
            </td>
            <td style="text-align: center">
                <a href="<?php print htmlspecialchars($ticket->getWebURL()); ?>">Link</a>
            </td>
            <td>
                <?php print ($ticket->getDescription()); ?>
            </td>
            <td style="text-align: center">
                <?php print htmlspecialchars($ticket->getFileURL()); ?>
            </td>
            <td style="text-align: center">
                <?php if (!isset($_GET["admin"])) :
                    if ($ticket->getStatus() == 1) :
                        print "Pending";
                    elseif ($ticket->getStatus() == 2) :
                        print "Assigned";
                    elseif ($ticket->getStatus() == 3) :
                        print "Rejected";
                    elseif ($ticket->getStatus() == 4) :
                        print "Completed";
                    elseif ($ticket->getStatus() == 5) :
                        print "Uploaded";
                    endif;
                else : ?>
                    <form action="index.php?admin" method="post">
                        <input type="hidden" name="id" value="<?php print $ticket->getID() ?>">
                        <select name="status">
                            <option value="1" <?php $ticket->getStatus() == 1 ? print "selected" : print ""; ?>>
                                Pending
                            </option>
                            <option value="2" <?php $ticket->getStatus() == 2 ? print "selected" : print ""; ?>>
                                Assigned
                            </option>
                            <option value="3" <?php $ticket->getStatus() == 3 ? print "selected" : print ""; ?>>
                                Rejected
                            </option>
                            <option value="4" <?php $ticket->getStatus() == 4 ? print "selected" : print ""; ?>>
                                Completed
                            </option>
                            <option value="5" <?php $ticket->getStatus() == 5 ? print "selected" : print ""; ?>>
                                Uploaded
                            </option>
                        </select>
                        <input type="submit">
                    </form>
                <?php endif; ?>
            </td>
            <td style="text-align: center">
                <?php if (!isset($_GET["admin"])) :
                    print htmlspecialchars($ticket->getAssigned());
                else : ?>
                    <form action="index.php?admin" method="post">
                        <input type="hidden" name="id" value="<?php print $ticket->getID() ?>">
                        <select name="assigned">
                            <option
                                value="Terence" <?php $ticket->getAssigned() == "Terence" ? print "selected" : print ""; ?>>
                                Terence
                            </option>
                            <option
                                value="Nicholas" <?php $ticket->getAssigned() == "Nicholas" ? print "selected" : print ""; ?>>
                                Nicholas
                            </option>
                            <option
                                value="Yuanhao" <?php $ticket->getAssigned() == "Yuanhao" ? print "selected" : print ""; ?>>
                                Yuanhao
                            </option>
                            <option
                                value="ChenXie" <?php $ticket->getAssigned() == "ChenXie" ? print "selected" : print ""; ?>>
                                ChenXie
                            </option>
                        </select>
                        <input type="submit">
                    </form>
                <?php endif;
                ?>

            </td>
            <td style="text-align: center">
                <?php if (!isset($_GET["admin"])) :
                    if ($ticket->getCompleted() == "0000-00-00 00:00:00") :
                        print "";
                    else :
                        print htmlspecialchars($ticket->getCompleted());
                    endif;
                else : ?>
                    <?php print htmlspecialchars($ticket->getCompleted()); ?>
                    <form action="index.php?admin" method="post">
                        <input type="hidden" name="id" value="<?php print $ticket->getID() ?>">
                        <input type="hidden" name="completed" value="1">
                        <input type="submit" value="Update Time">
                    </form>

                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

