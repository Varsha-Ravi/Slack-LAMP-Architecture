<?php
  include_once $_SESSION['basePath'].'errors.php';
  require_once $_SESSION['basePath'].'models/connect.php';

  class HomeModel {
    private $dbConVar;
    private $channels = array();
    private $messages = array();

    public function validMySQL($data) {
      $data = stripslashes($data);
      $data = htmlentities($data);
      $data = strip_tags($data);
      $data = mysql_real_escape_string($data);
      return $data;
    }

    public function validateInputs($data) {
      $data = trim($data);
      //$data = stripslashes($data);
      $data = htmlspecialchars($data);
      return $data;
    }

    public function retrieveChannels()
    {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $this->channels = array();
      $retChannels = "SELECT channel_name
                      FROM workspace_channels
                      WHERE channel_id IN (
                      SELECT DISTINCT channel_id
                      FROM inside_channel
                      WHERE user_id = '".$_SESSION['userid']."')";
      $result = mysqli_query($conn, $retChannels);
      if (mysqli_num_rows($result) > 0)
      {
        while ($row = $result->fetch_assoc())
        {
            array_push($this->channels, $this->validateInputs($row['channel_name']));
        }
      }
      mysqli_free_result($result);
      $dbConVar->closeConnectionObject($conn);
      return $this->channels;
    }

    public function retrieveMessages($channelName)
    {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $this->messages = array();
      $retMessages = "SELECT channel_id, channel_messages.user_id, first_name, last_name, message, msg_id, created_time
                      FROM channel_messages INNER JOIN user_info on channel_messages.user_id = user_info.user_id
                      WHERE channel_id
                      IN (
                      SELECT channel_id
                      FROM workspace_channels
                      WHERE channel_name = '$channelName')
                      ORDER BY created_time ASC";
      $result = mysqli_query($conn, $retMessages);
      if (mysqli_num_rows($result) > 0)
      {
          while ($row = $result->fetch_assoc())
          {
            $row['message'] = $this->validateInputs($row['message']);
            array_push($this->messages, $row);
          }
      }
      if ($result) {
      mysqli_free_result($result);
      }
      $dbConVar->closeConnectionObject($conn);
      return $this->messages;
    }

    public function getChannelId($channelName) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $chId = NULL;
      $getChannelId = "SELECT channel_id
                       FROM workspace_channels
                       WHERE channel_name = '$channelName'";
      $result = mysqli_query($conn, $getChannelId);
      if (mysqli_num_rows($result) > 0)
      {
        while ($row = $result->fetch_assoc())
        {
          $chId = $row['channel_id'];
        }
      }
      mysqli_free_result($result);
      return $chId;
      $dbConVar->closeConnectionObject($conn);
    }

    public function insertMessage($channelName, $message, $threadId, $type)
    {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $affectedRows = NULL;
      $chId = $this->getChannelId($channelName);
      if ($chId != NULL)
      {
        $msgId = NULL;
        $getMsgId = "SELECT msg_id
                     FROM channel_messages
                     ORDER BY msg_id
                     DESC LIMIT 1";
        $result = mysqli_query($conn, $getMsgId);
        if (mysqli_num_rows($result) > 0)
        {
          while ($row = $result->fetch_assoc())
          {
            $msgId = $row['msg_id'];
          }
        } else {
          $msgId = 0;
        }
        if ($result) {
          mysqli_free_result($result);
        }
        if ($msgId >= 0) {
          $msgId++;
          $dependency = NULL;
          if ($threadId == NULL) {
            $dependency = $msgId;
          } else {
            $dependency = $threadId;
          }

          $stmt = $conn->prepare("INSERT INTO channel_messages (channel_id, user_id, msg_id, message, type, dependency)
                                  VALUES (?,?,?,?,?,?)");
          $stmt->bind_param("ssssss", $chId, $_SESSION['userid'], $msgId, $message, $type, $dependency);
          $stmt->execute();
          //if ($stmt->affected_rows > 0) {}
          $affectedRows = $stmt->affected_rows;
          $stmt->close();
        }
    }
      $dbConVar->closeConnectionObject($conn);
      return $affectedRows;
    }

    public function updateMessageType($threadId, $type) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $updateType = "UPDATE channel_messages
                     SET type = $type
                     WHERE msg_id = $threadId";
      $result = mysqli_query($conn, $updateType);
      $affectedRows = mysqli_affected_rows($conn);
      return $affectedRows;
    }

    public function addUserToChannel($userId, $channelName, $workspaceUrl)
    {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $channelId = NULL;
      $affectedRows = NULL;
      $getChannelId = "SELECT channel_id
                       FROM workspace_channels
                       WHERE channel_name = '$channelName' AND url = '$workspaceUrl'";
      $result = mysqli_query($conn, $getChannelId);
      if (mysqli_num_rows($result) > 0)
      {
        while ($row = $result->fetch_assoc())
        {
          $channelId = $row['channel_id'];
        }
      }
      mysqli_free_result($result);
      if ($channelId != NULL) {
        $stmt = $conn->prepare("INSERT INTO inside_channel (channel_id, user_id)
                                VALUES (?,?)");
        $stmt->bind_param("ss", $channelId, $userId);
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
      }
      $dbConVar->closeConnectionObject($conn);
      return $affectedRows;
    }

    public function createChannel($channelName, $purpose, $type, $workspaceUrl) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $isUserAdded = 0;
      $channelId = NULL;
      $stmt = $conn->prepare("INSERT INTO workspace_channels (channel_id, channel_name, purpose, url, user_id, type)
                              VALUES (?,?,?,?,?,?)");
      $stmt->bind_param("ssssss", $channelId, $channelName, $purpose, $workspaceUrl, $_SESSION['userid'], $type);
      $stmt->execute();
      $affectedRows = $stmt->affected_rows;
      $stmt->close();
      if ($affectedRows == 1) {
        $isUserAdded = $this->addUserToChannel($_SESSION['userid'], $channelName, $workspaceUrl);
      }
      $dbConVar->closeConnectionObject($conn);
      $result = array("channelStatus" => $affectedRows, "userStatus" => $isUserAdded);
      return $result;
    }

    public function retrieveReplies($threadId) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $this->replies = array();
      $getReplies = "SELECT user_id, msg_id, message, created_time
                     FROM channel_messages
                     WHERE dependency = $threadId;
                     ORDER BY created_time ASC";
      $result = mysqli_query($conn, $getReplies);
      if (mysqli_num_rows($result) > 0) {
        while ($row = $result->fetch_assoc())
        {
          $row['message'] = $this->validateInputs($row['message']);
          array_push($this->replies, $row);
        }
      }
      if ($result) {
        mysqli_free_result($result);
      }
      $dbConVar->closeConnectionObject($conn);
      return $this->replies;
    }

    public function getInfoForMsgReaction($msgId, $emoId) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $info = array();
      $getUsers = "SELECT users, count
                   FROM reactions
                   WHERE msg_id = '$msgId' AND emo_id = $emoId";
      $result = mysqli_query($conn, $getUsers);
      if (mysqli_num_rows($result) == 1) {
        while ($row = $result->fetch_assoc()) {
          $info['users'] = $row['users'];
          $info['count'] = $row['count'];
        }
      }
      if ($result) {
        mysqli_free_result($result);
      }
      $dbConVar->closeConnectionObject($conn);
      return $info;
    }

    public function getEmoId($emoName) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $emoId = NULL;
      $getEmoId = "SELECT emo_id
                   FROM emoticons
                   WHERE emo_name = '$emoName'";
      $result = mysqli_query($conn, $getEmoId);
      if (mysqli_num_rows($result) == 1) {
        while ($row = $result->fetch_assoc()) {
          $emoId = $row['emo_id'];
        }
      }
      if ($result) {
        mysqli_free_result($result);
      }
      $dbConVar->closeConnectionObject($conn);
      return $emoId;
    }

    public function handleUserReaction($msgId, $emoId, $info, $isInsert) {
      $dbConVar = new dbConnect();
      $conn = $dbConVar->createConnectionObject();
      $users = $info['users'];
      $count = NULL;
      if ($isInsert === true) {
        $count = $info['count'] + 1;
      } else {
        if ($count > 0) {
          $count = $info['count'] - 1;
        }
      }
      $affectedRows = NULL;
      if ($emoId != NULL && $count != NULL) {
        if($users != NULL) {
            if ($isInsert == "true") {
              $users = $users.$_SESSION['userid'].";";
            } else {
              str_replace(";".$_SESSION['userid'].";", ";", $users);
            }
        } else {
            if ($isInsert == "true") {
              $users = ";".$_SESSION['userid'].";";
            }
        }
          $stmt = $conn->prepare("INSERT INTO reactions (msg_id, emo_id, users, count)
                                  VALUES (?,?,?,?)");
          $stmt->bind_param("ssss", $msgId, $emoId, $users, $count);
          $stmt->execute();
          $affectedRows = $stmt->affected_rows;
          $stmt->close();
      }
      $dbConVar->closeConnectionObject($conn);
      return $affectedRows;
    }
  }

?>
