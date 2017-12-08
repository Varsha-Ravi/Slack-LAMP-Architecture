<?php
  session_start();
  $_SESSION["basePath"] = "../";
  require_once $_SESSION["basePath"].'controllers/login.php';
  require_once $_SESSION['basePath'].'controllers/home.php';
  require_once $_SESSION['basePath'].'controllers/profile.php';

  $channelName = NULL;
  $textArea = NULL;
  $channelResponse = NULL;
  $channelHeading = NULL;
  $chStatus = NULL;
  $channelCreator = array();
  $newInviteUserResponse = array();
  $workspaceUrl = "musicf17.slack.com";
  $homeControlVar = new HomeController();
  $loginControllerVar = new LoginController();
  $profileControllerVar = new ProfileController();

  if (isset($_GET['register']) && $_GET['register'] == 'new') {
    $firstName = $_POST["firstName"];
    $lastName = $_POST["lastName"];
    $email = $_POST["email"];
    $userId = $_POST["userId"];
    $password = $_POST["password"];
    $workspaceUrl = "musicf17.slack.com";
    $loginControllerVar->registerNewUser($userId, $email, $password, $firstName, $lastName, $workspaceUrl);
  }

  if (isset($_POST['logout']) && $_POST['logout'] == "logout") {
    session_destroy();
    header("location:login.php", true, 303);
  }

  if (isset($_POST["channel"])) {
    global $channelName;
    $channelName = $_POST["channel"];
  }

  if (isset($_POST["channelHeading"])) {
		global $channelHeading;
		$channelHeading = $_POST["channelHeading"];
	}

  if (isset($_POST["chStatus"])) {
		global $chStatus;
    if ($_POST["chStatus"] == "archived") {
			$chStatus = "Unarchive";
			$_POST["chStatus"] = $chStatus;
		} else if ($_POST["chStatus"] == "unarchived") {
			$chStatus = "Archive";
			$_POST["chStatus"] = $chStatus;
		} else {
			$chStatus = $_POST["chStatus"];
		}
	}

  if (isset($_POST["textarea"])) {
    global $textArea;
    $textArea = $_POST["textarea"];
    insertMessage($textArea);
  }

  if (isset($_POST['newChannel']) && $_POST['newChannel'] == "newChannel") {
    global $channelName;
    global $chStatus;
    $channelName = $_POST['channel'];
    $purpose = $_POST['purpose'];
    $channeltype = $_POST['channelType'];

    if ($_POST["chStatus"] == "archived") {
			$chStatus = "Unarchive";
			$_POST["chStatus"] = $chStatus;
		} else if ($_POST["chStatus"] == "unarchived") {
			$chStatus = "Archive";
			$_POST["chStatus"] = $chStatus;
		} else {
			$chStatus = $_POST["chStatus"];
		}
    createChannel($channelName, $purpose, $channeltype, $_POST["newUserSearch"]);
  }

  if (isset($_POST['inviteUsersExistingChannel']) && $_POST['inviteUsersExistingChannel'] == 'inviteUser') {
    global $newInviteUserResponse;
    global $channelName;
    global $channelHeading;
    global $chStatus;
    global $workspaceUrl;
    global $homeControlVar;
    $temp = array();

    if ($_POST["addUserExistingChannel"] != NULL && !empty($_POST["addUserExistingChannel"])) {
      $userList = array();
      $userList = $_POST["addUserExistingChannel"];
      $isCreate = "false";
      $newInviteUserResponse = $homeControlVar->inviteUsersToChannel($userList, $channelName, $workspaceUrl, $isCreate);
      if ($newInviteUserResponse['success'] != NULL && !empty($newInviteUserResponse['success']) &&
        empty($newInviteUserResponse['failed'])) {
          $temp = $newInviteUserResponse['success'];
          //use a popup
          } else {
        $temp = $newInviteUserResponse['failed'];
          //use a popup
        }
    }
    $_SESSION['channel'] = $channelName;
    $_SESSION['channelHeading'] = $channelHeading;
    $_SESSION['chStatus'] = $chStatus;
    unset($_POST['inviteUsersExistingChannel']);
    unset($_POST["channel"]);
    unset($_POST['addUserExistingChannel']);
    $homeControlVar->redirectToHome();
  }

  if (isset($_POST['removeUsersExistingChannel']) && $_POST['removeUsersExistingChannel'] == 'removeUser') {
    global $channelName;
    global $channelHeading;
    global $chStatus;
    global $workspaceUrl;
    $userList = array();
    $removedUserResponse = array();
    $userList = $_POST["removeUserExistingChannel"];
    //print_r($userList);
    $removedUserResponse = $homeControlVar->removeUsersFromChannel($userList, $channelName, $workspaceUrl);
    //print_r($removedUserResponse);
    if ($removedUserResponse['success'] != NULL && !empty($removedUserResponse['success']) &&
      empty($removedUserResponse['failed'])) {
        $temp = $removedUserResponse['success'];
        //use a popup
    } else {
          $temp = $removedUserResponse['failed'];
        //use a popup
    }
    $_SESSION['channel'] = $channelName;
    $_SESSION['channelHeading'] = $channelHeading;
    $_SESSION['chStatus'] = $chStatus;
    unset($_POST['removeUsersExistingChannel']);
    unset($_POST["channel"]);
    unset($_POST['removeUserExistingChannel']);
    $homeControlVar->redirectToHome();
  }

  function insertMessage($textArea) {
    global $homeControlVar;
    global $channelName;
    global $channelHeading;
    global $workspaceUrl;
    global $chStatus;
    $threadId = NULL;
    $messageType = 'post';
    unset($_POST["textarea"]);
    $image_path = NULL;
    $snippet = NULL;
    $message = $homeControlVar->insertMessage($channelName,$textArea,$image_path,$snippet,$threadId,$messageType, $workspaceUrl);
    //if ($message != 'false') {
      $_SESSION['channel'] = $channelName;
      $_SESSION['channelHeading'] = $channelHeading;
      $_SESSION['chStatus'] = $chStatus;
      $homeControlVar->redirectToHome();
    //}
  }

  function createChannel($channelName, $purpose, $channeltype, $invitedUsers) {
    global $workspaceUrl;
    //global $newInviteUserResponse;
    global $homeControlVar;
    global $channelResponse;
    global $channelCreator;
    global $channelHeading;
    global $chStatus;

    $channelResponse = $homeControlVar->createNewChannel($channelName, $purpose, $channeltype, $workspaceUrl);
    if ($channelResponse == "true") {
    $admins = array();
    $temp = array();
    $temp = $homeControlVar->getAdmins();
    foreach ($temp as $value) {
      array_push($admins, $value['user_id']);
    }
    $userList = array();
    $userList = $invitedUsers;
    array_push($userList, $_SESSION['userid']);
    $users = array_merge($userList,$admins);
    //echo $users;
    $isCreate = "true";
    $channelCreator = $homeControlVar->inviteUsersToChannel($users, $channelName, $workspaceUrl, $isCreate);
    $_SESSION['channel'] = $channelName;
    if ($channeltype == "Public") {
      $channelHeading = "#"." ".$channelName;
    } else {
      $channelHeading = "∆"." ".$channelName;
    }
    $_SESSION['channelHeading'] = $channelHeading;
    $_SESSION['chStatus'] = $chStatus;
    }
    unset($_POST['newChannel']);
    unset($_POST["channel"]);
    unset($_POST['purpose']);
    unset($_POST['channelType']);
    $homeControlVar->redirectToHome();
    //var_dump($newInviteUserResponse);
  }

  if(isset($_POST['UserName'])){
    global $homeControlVar;
    global $workspaceUrl;
    $userList =$homeControlVar->getUsersForPattern($_POST['UserName'], $workspaceUrl);
    echo json_encode($userList);
  }

  if(isset($_POST['thread_insertion'])) {
    // global $thread_message;
    global $homeControlVar;
    global $channelName;
    global $workspaceUrl;
    $thread_message = $_POST['thread_insertion']['reply_message'];
    $thread_id = $_POST['thread_insertion']['thread_id'];
    $channelName = $_POST['thread_insertion']['channel_name'];
    $messageType = 'reply';
    $image_path = NULL;
    $snippet = NULL;
    $message = $homeControlVar->insertMessage($channelName,$thread_message,$image_path,$snippet,$thread_id,$messageType,$workspaceUrl);
  }

  if (isset($_POST['deletePostID'])) {
    global $homeControlVar;
    global $workspaceUrl;
    global $channelName;

    $msgID = $_POST['deletePostID'];
    //$channelName = $_POST['deletePost']['ch_name'];
    $isSuccess = $homeControlVar->deletePostsFromChannel($msgID, $channelName, $workspaceUrl);
    $_SESSION['channel'] = $channelName;
    $_SESSION['channelHeading'] = $channelHeading;
    $_SESSION['chStatus'] = $chStatus;
    echo $isSuccess;
  }

  if (isset($_POST['insertReaction'])) {
      global $homeControlVar;
      global $workspaceUrl;
      $likeCount = NULL;
      $dislikeCount = NULL;
      $reactionResponse = array();
      $msgId = $_POST['insertReaction']['msgId'];
      $emoName = $_POST['insertReaction']['emoName'];
      //$isInsert = $_POST['insertReaction']['isInsert'];
      //$reactionsData = array();
      //$reactionsData=json_decode(stripslashes($_POST['reactionsData']));
      $reactionHandling = $homeControlVar->handleReactionForMsg($msgId, $emoName, $workspaceUrl);
      $reactionResponse = $homeControlVar->getReactionInfoForMsg($msgId, $emoName);

      //if like +1 then dislike -1 & vice-versa
      if ($emoName == "like" || $emoName == "dislike") {
        $infoLike = array();
        $infoLike = $homeControlVar->getReactionInfoForMsg($msgId, "like");
        $likedUsers = isset($infoLike['users']) ? $infoLike['users'] : NULL;
        $isLikeExists = $homeControlVar->isUserExistsForReaction($likedUsers);
        $infoDislike = array();
        $infoDislike = $homeControlVar->getReactionInfoForMsg($msgId, "dislike");
        $disLikedUsers = isset($infoDislike['users']) ? $infoDislike['users'] : NULL;
        $isDislikeExists = $homeControlVar->isUserExistsForReaction($disLikedUsers);
        $responseArray = array();

        if($emoName == "like" && $isLikeExists == "true") {
            if ($isDislikeExists == "true") {
              $responseArray = $homeControlVar->handleReactionForMsg($msgId, "dislike", $workspaceUrl);
              //$result = $homeControlVar->getReactionInfoForMsg($msgId, "dislike");
              if ($responseArray['result'] == "true") {
                $dislikeCount = (string) (intval($infoDislike['count']) - intval(1));
              } else {
                $dislikeCount = (string) (intval($infoDislike['count']));
              }
            }
        } else if ($emoName == "dislike" && $isDislikeExists == "true") {
            if ($isLikeExists == "true") {
              $responseArray = $homeControlVar->handleReactionForMsg($msgId, "like", $workspaceUrl);
              //$result = $homeControlVar->getReactionInfoForMsg($msgId, "dislike");
              if ($responseArray['result'] == "true") {
                $likeCount = (string) (intval($infoLike['count']) - intval(1));
              } else {
                 $likeCount = (string) (intval($infoLike['count']));
              }
            }
        }
      }

      $computedResp = array('emoResp'=>$reactionResponse, 'likeCount'=>$likeCount, 'dislikeCount'=>$dislikeCount);
      echo json_encode($computedResp);//$reactionResponse['count'];//json_encode($reactionResponse); $reactionHandling["message"];
      //echo json_encode($reactionResponse);
    }

    // image insertion
    if(!empty($_FILES) && $_FILES['image_uploaded_post'] != NULL && !empty($_FILES['image_uploaded_post'])) {
      global $homeControlVar;
      global $profileControllerVar;
      global $workspaceUrl;
      global $channelName;
      global $channelHeading;
      global $chStatus;

      $message = NULL;
      $thread_message = NULL;
      $snippet = NULL;
      $thread_id = NULL;
      $messageType = 'post';

      $response = array("result"=>NULL, "message"=>NULL);
      $target_dir = "images/messages/";
      $uploadedFileName = $_FILES["image_uploaded_post"]["name"];
      $image_path = $target_dir.$uploadedFileName;
      $imageType = $profileControllerVar->checkImageType($_FILES['image_uploaded_post']['tmp_name']);
      if ($imageType == "" && $imageType == NULL) {
        $response["result"] = "false";
        $response["message"] = "File is not an image.";
      } else {
        $response = $homeControlVar->uploadFile($_FILES["image_uploaded_post"],
                    $channelName, $thread_message, $image_path,
                    $snippet, $thread_id, $messageType, $workspaceUrl);
      }
      $_SESSION['channel'] = $channelName;
      $_SESSION['channelHeading'] = $channelHeading;
      $_SESSION['chStatus'] = $chStatus;
      echo json_encode($response);
    }


    //image from url
    if(isset($_POST["image_upload_from_url_path"])){
      global $homeControlVar;
      global $profileControllerVar;
      global $workspaceUrl;
      global $channelName;
      global $channelHeading;
      global $chStatus;

      $message = NULL;
      $thread_message = NULL;
      $snippet = NULL;
      $thread_id = NULL;
      $messageType = 'post';
      //$channelName = $_POST["image_insertion_from_url"]["retChannel"];
      $image_path = $_POST["image_upload_from_url_path"];
      $image_type = $profileControllerVar->checkImageType($image_path);
      if ($image_type != NULL && $image_type != "") {
        $message = $homeControlVar->insertMessage($channelName,$thread_message,$image_path,$snippet,$thread_id,$messageType,$workspaceUrl);
      }
      $_SESSION['channel'] = $channelName;
      $_SESSION['channelHeading'] = $channelHeading;
      $_SESSION['chStatus'] = $chStatus;
      echo $message;
    }
    // code insertion
    if(isset($_POST["snippet_text"])){
      global $homeControlVar;
      global $profileControllerVar;
      global $workspaceUrl;
      global $channelName;
      global $channelHeading;
      global $chStatus;

      $thread_message = NULL;
      $snippet = $_POST["snippet_text"];
      $image_path = NULL;
      $threadId = NULL;
      $messageType = 'post';
      $message = $homeControlVar->insertMessage($channelName,$thread_message,$image_path,$snippet,$thread_id,$messageType,$workspaceUrl);

      $_SESSION['channel'] = $channelName;
      $_SESSION['channelHeading'] = $channelHeading;
      $_SESSION['chStatus'] = $chStatus;
      if ($message == "Message is inserted") {
        echo $message;
      } else {
        echo "false";
      }
    }

    if (isset($_POST["channel_status"])) {
      global $homeControlVar;
      global $workspaceUrl;
      global $channelName;
      $status = $_POST["channel_status"]["status"];
      $channelName = $_POST["channel_status"]["channel"];
      $isSuccess = $homeControlVar->updateChannelStatus($channelName, $workspaceUrl, $status);
      echo $isSuccess;
    }

    if (isset($_POST["getUsersForChannel"])) {
      global $homeControlVar;
      global $workspaceUrl;
      global $channelName;
      $channelName = $_POST["getUsersForChannel"];
      $userList = $homeControlVar->retUsersFromChannel($channelName, $workspaceUrl);
      echo json_encode($userList);
    }

  if (isset($_POST["inviteUsersForChannel"])) {
    global $homeControlVar;
    global $workspaceUrl;
    global $channelName;
    $channelName = $_POST["inviteUsersForChannel"];
    $userList = $homeControlVar->retInviteUsersForChannel($channelName, $workspaceUrl);
    echo json_encode($userList);
  }

?>
