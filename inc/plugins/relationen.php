<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.");
}
//error_reporting ( -1 );
//ini_set ( 'display_errors', true );

$plugins->add_hook('member_profile_end', 'profile_relation');
$plugins->add_hook('misc_start', 'misc_relation');
$plugins->add_hook('global_intermediate', 'global_relation_alert');

function relationen_info()
{
    return array(
        "name"			=> "RPG Relationen im Profil",
        "description"	=> "Hier können Relationen im Profil selbst verwaltet werden.",
        "website"		=> "",
        "author"		=> "Alex",
        "authorsite"	=> "",
        "version"		=> "1.0",
        "guid" 			=> "",
        "codename"		=> "",
        "compatibility" => "*"
    );
}

function relationen_install()
{
    global $db;
    if($db->engine=='mysql'||$db->engine=='mysqli')
    {
        $db->query("CREATE TABLE `".TABLE_PREFIX."relationen` (
          `rid` int(10) NOT NULL auto_increment,
          `username` varchar(255) NOT NULL,
           `anfrager` int(10) NOT NULL,
          `angefragte` int(10) NOT NULL,
          `kat` varchar(255) NOT NULL,
          `art` varchar(255) NOT NULL,
          `shortfacts` varchar(255) NOT NULL,
		  `npcavatar` varchar(500) NOT NULL,
          `ok` int(11) NOT NULL default '0',
          PRIMARY KEY (`rid`)
        ) ENGINE=MyISAM".$db->build_create_table_collation());

    }
}

function relationen_is_installed()
{
    global $db;
    if($db->table_exists("relationen"))
    {
        return true;
    }
    return false;
}

function relationen_uninstall()
{
    global $db;
    if($db->table_exists("relationen"))
    {
        $db->drop_table("relationen");
    }

    rebuild_settings();
}

function relationen_activate()
{
    global $db;

    require MYBB_ROOT."/inc/adminfunctions_templates.php";
    find_replace_templatesets("header", "#".preg_quote('{$pm_notice}')."#i", '{$relationen_alert} {$pm_notice}');
}

function relationen_deactivate()
{
    global $db;
    require MYBB_ROOT."/inc/adminfunctions_templates.php";
    find_replace_templatesets("header", "#".preg_quote('{$relationen_alert}')."#i", '', 0);
}

function profile_relation(){
    global $db, $mybb, $memprofile, $templates, $relationen, $edit, $relationen_formular,  $relationen_profil_bit, $avatar, $theme ;

    require_once MYBB_ROOT."inc/datahandlers/pm.php";
    $pmhandler = new PMDataHandler();

    //Anfrager
    $anfrager = $mybb->user['uid'];

    //Bei dem Angefragt wird
    $angefragte = $memprofile['uid'];

    $username = $mybb->user['username'];

    if($mybb->user['uid'] != '0'){
        if($memprofile['uid'] != $mybb->user['uid']){
            eval("\$relationen_formular = \"" . $templates->get ("relationen_formular") . "\";");
        } else {
            eval("\$relationen_formular = \"" . $templates->get ("relationen_formular_npc") . "\";");
        }
    }


//Eintragen von existierenden User
    if($mybb->user['uid'] != '0'){
        if(isset($_POST['add'])) {
            $anfrager = $anfrager;
            $angefragte = $angefragte;
            $username= $username;
            $kat = $_POST['kat'];
            $art = $_POST['art'];
            $shortfacts = "";

            $new_record = array(
                "username" => $db->escape_string($username),
                "anfrager" => $db->escape_string($anfrager),
                "angefragte" => $db->escape_string($angefragte),
                "kat" => $db->escape_string($kat),
                "art" => $db->escape_string($art),
                "shortfacts" => $db->escape_string($shortfacts)
            );

            $db->insert_query("relationen", $new_record);
            redirect("member.php?action=profile&uid={$memprofile['uid']}");
        }

//NPC eintragen

        if (isset($_POST['npc_add'])) {
            $angefragte = 0;
            $anfrager = $mybb->user['uid'];
            $username= $_POST['chara_name'];
            $kat = $_POST['kat'];
            $art = $_POST['art'];
            $shortfacts = $_POST['shortfacts'];
            $ok = 1;

            $new_record = array(
                "username" => $db->escape_string($user),
                "anfrager" => $db->escape_string($anfrager),
                "angefragte" => $db->escape_string($angefragte),
                "kat" => $db->escape_string($kat),
                "art" => $db->escape_string($art),
                "shortfacts" => $db->escape_string($shortfacts),
                "ok" => $db->escape_string($ok)
            );

            $db->insert_query("relationen", $new_record);
            redirect("member.php?action=profile&uid={$memprofile['uid']}");
        }



    }

//Im Profil ausgeben
    $uid = $memprofile['uid'];

    $select = $db->query("   Select *
     FROM " . TABLE_PREFIX . "relationen r
    LEFT JOIN " . TABLE_PREFIX . "users u
    ON r.angefragte = u.uid
    LEFT JOIN " . TABLE_PREFIX . "userfields uf
    ON u.uid = uf.ufid
    WHERE r.anfrager = '" . $uid . "'
    AND r.ok = '1'
    ORDER BY u.username ASC, r.username ASC
  "  );
    while ($row = $db->fetch_array($select)) {

        if ($row['angefragte'] == 0) {
            $rid = $row['rid'];
            $npc_query = $db->query("Select username
            FROM ".TABLE_PREFIX."relationen 
            WHERE anfrager = '" . $uid . "'
            AND rid = '".$rid."'
            ");

            $npc_name = $db->fetch_array($npc_query);
            $user = $npc_name['username'];
            $shortfacts = $row['shortfacts'];


            $rel_avatar = "<img src='{$theme['imgdir']}/noavatar.png' style='width: 60px;'>";


            if ($mybb->user['uid'] == $memprofile['uid'] || $mybb->usergroup['canmodcp'] == 1 || $mybb->usergroup['canacp'] == 1) {
                $delete = "<a href='member.php?action=profile&del=$row[rid]'><i class=\"fa fa-times\" aria-hidden=\"true\"></i></a>";

                eval("\$edit = \"" . $templates->get("relationen_bit_profil_edit_npc") . "\";");
            }
        } else{

            $username = format_name($row['username'], $row['usergroup'], $row['displaygroup']);
            $user = build_profile_link($username, $row['uid']);
            $shortfacts = $row['fid40'];
            if (!empty($row['avatar'])) {
                $rel_avatar = "<img src='{$row['avatar']}' style='width: 60px;'>";
            } else {
                $rel_avatar = "<img src='{$theme['imgdir']}/noavatar.png' style='width: 60px;'>";
            }

            if ($mybb->user['uid'] == $memprofile['uid'] || $mybb->usergroup['canmodcp'] == 1 || $mybb->usergroup['canacp'] == 1) {
                $delete = "<a href='member.php?action=profile&del=$row[rid]'><i class=\"fa fa-times\" aria-hidden=\"true\"></i></a>";
                eval("\$edit = \"" . $templates->get("relationen_bit_profil_edit") . "\";");

            }
        }

//Gäste dürfen kein Avatar sehen
        if ($mybb->user['uid'] == '0') {
            $rel_avatar = "<img src='{$theme['imgdir']}/noavatar.png' style='width: 60px;'>";
        }


        if ($row['kat'] == 'familie') {
            eval("\$familie .= \"" . $templates->get("relationen_profil_bit") . "\";");
        } elseif ($row['kat'] == 'freunde') {
            eval("\$freunde .= \"" . $templates->get("relationen_profil_bit") . "\";");
        } elseif ($row['kat'] == 'bekannte') {
            eval("\$bekannte .= \"" . $templates->get("relationen_profil_bit") . "\";");
        } elseif ($row['kat'] == 'liebe') {
            eval("\$liebe .= \"" . $templates->get("relationen_profil_bit") . "\";");
        } elseif ($row['kat'] == 'feinde') {
            eval("\$feinde .= \"" . $templates->get("relationen_profil_bit") . "\";");
        } elseif ($row['kat'] == 'vergangen') {
            eval("\$vergangen .= \"" . $templates->get("relationen_profil_bit") . "\";");
        }
        eval("\$relationen_profil_bit .= \"" . $templates->get("relationen_profil_bit") . "\";");
    }


    eval("\$relationen_bit_profil = \"" . $templates->get ("relationen_bit_profil") . "\";");

    eval("\$relationen = \"" . $templates->get ("relationen") . "\";");

    $del = $mybb->input['del'];
    if($del){
        $db->delete_query("relationen", "rid = '$del'");
        redirect("member.php?action=profile&uid={$memprofile['uid']}");
    }

    if(isset($mybb->input['edit'])){
        $getrid = $mybb->input['getrid'];
        $anfrager = $mybb->input['anfrager'];
        $angefragte = $mybb->input['angefragte'];
        $username= $mybb->input['chara_name'];
        $kat = $mybb->input['kat'];
        $art = $mybb->input['art'];
        $shortfacts = $mybb->input['shortfacts'];


        if($mybb->user['uid'] == $memprofile['uid'] && $anfrager != 0){
            //Wenn Angefragter editiert
            $select = $db->query("SELECT *
		 FROM ".TABLE_PREFIX."relationen r
		LEFT JOIN ".TABLE_PREFIX."users u
		ON r.angefragte = u.uid
		WHERE r.rid = '".$getrid."'
		");
            $row = $db->fetch_array($select);

            $pm_change = array(
                "subject" => "Relation geändert",
                "message" => "Liebe/r {$row['username']}, <br /> ich habe die Relation bei mir geändert. Schau bitte, ob das für dich in Ordnung ist.",
                //to: wer muss die anfrage bestätigen
                "fromid" => $anfrager,
                //from: wer hat die anfrage gestellt
                "toid" => $angefragte
            );
            // $pmhandler->admin_override = true;
            $pmhandler->set_data ($pm_change);
            if (!$pmhandler->validate_pm ())
                return false;
            else {
                $pmhandler->insert_pm ();
            }
            $db->query("UPDATE ".TABLE_PREFIX."relationen SET anfrager = '".$anfrager."', angefragte = '".$angefragte."', username= '".$user."',kat = '".$kat."', art = '".$art."', ok = '1',  shortfacts = '".$shortfacts."'  WHERE rid = '".$getrid."'");
            redirect("member.php?action=profile&uid={$memprofile['uid']}");
        }

        if($angefragte == $row['angefragte'] && $angefragte != '0') {
            //Wenn der Angefragte editiert
            $select = $db->query("SELECT *
		 FROM ".TABLE_PREFIX."relationen r
		LEFT JOIN ".TABLE_PREFIX."users u
		ON r.anfrager = u.uid
		WHERE r.rid = '".$getrid."'
		");
            $row = $db->fetch_array($select);
            $pm_change = array(
                "subject" => "Relation geändert",
                "message" => "Liebe/r {$row['username']}, <br /> ich habe die Relation bei dir geändert. Bitte schau nach, ob sie für dich in Ordnung ist. ",
                //to: wer muss die anfrage bestätigen
                "fromid" => $angefragte,
                //from: wer hat die anfrage gestellt
                "toid" => $anfrager
            );
            // $pmhandler->admin_override = true;
            $pmhandler->set_data ($pm_change);
            if (!$pmhandler->validate_pm ())
                return false;
            else {
                $pmhandler->insert_pm ();
            }
            $db->query("UPDATE ".TABLE_PREFIX."relationen SET anfrager = '".$anfrager."', angefragte = '".$angefragte."', kat = '".$kat."', art = '".$art."', ok = '1'  WHERE rid = '".$getrid."'");
            redirect("member.php?action=profile&uid={$memprofile['uid']}");
        }
    }

    if(isset($mybb->input['npc_edit'])){

        $getrid = $mybb->input['getrid'];
        if($mybb->request_method == "post" AND $_FILES["fileToUpload"]["name"] != "") {

            $idla = $mybb->user['uid'];
            $npcname = $mybb->get_input('chara_name');
            $ownid = str_replace(" ","_",$npcname);

            $target_dir = "uploads/npcs/";
            $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);


            $ext=pathinfo($target_file,PATHINFO_EXTENSION);

            $new_name = "".$ownid.".".$ext;
            $target_new = $target_dir . basename($new_name);

            $uploadOk = 1;
            $avatar_error = "";
            $avatar_error1 = "";
            // Get Image Dimension
            $fileinfo = @getimagesize($_FILES["fileToUpload"]["tmp_name"]);
            $width = $fileinfo[0];
            $height = $fileinfo[1];

            $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
// Check if image file is a actual image or fake image
            if(isset($_POST["submit"])) {
                $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
                if($check !== false) {
                    $uploadOk = 1;
                } else {
                    $avatar_error = "Die Datei ist kein Bild.";
                    $uploadOk = 0;
                }
            }
// Check file size
            if ($_FILES["fileToUpload"]["size"] > 500000) {
                $avatar_error = "Die Datei ist zu Groß. Max. 500kb";
                $uploadOk = 0;
            }

// Validate image file dimension
            if ($width > "100" || $height > "100") {
                $avatar_error = "Die Datei ist zu Groß. Max. 100x100";
                $uploadOk = 0;
            }

// Allow certain file formats
            if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
                && $imageFileType != "gif" ) {
                $avatar_error = "Die Datei hat den falschen Typ. Es ist nur JPG, JPEG, PNG und GIF erlaubt.";
                $uploadOk = 0;
            }

// Check if $uploadOk is set to 0 by an error
            if ($uploadOk == 0) {
                $avatar_error1 = "Deine Datei wurde nicht hochgeladen.";
// if everything is ok, try to upload file
            } else {
                if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_new)) {

                } else {
                    $avatar_error = "Es gab einen Error beim Uploaden deiner Datei.";
                }
            }
        };

        if ($uploadOk == 1 OR $_FILES["fileToUpload"]["name"]== "") {


            if($target_new == "") {
                $npcpic = $_POST['npcpic'];
            }
            else {
                $npcpic = $target_new;
            }

            // NPC bearbeiten
            if($mybb->request_method == "post") {
                $update_record = array(
                    "anfrager" => $db->escape_string($mybb->get_input('anfrager')),
                    "angefragte" => $db->escape_string($mybb->get_input('angefragte')),
                    "username" => $db->escape_string($mybb->get_input('chara_name')),
                    "kat" => $db->escape_string($mybb->get_input('kat')),
                    "art" => $db->escape_string($mybb->get_input('art')),
                    "shortfacts" => $db->escape_string($mybb->get_input('shortfacts')),
                    "ok" => "1",
                    "npcavatar" => $npcpic
                );

                $db->update_query("relationen", $update_record, "rid = '$getrid'");

                redirect("member.php?action=profile&id={$uid}");

            }

        };
    }

}


function global_relation_alert(){
    global $db, $mybb, $templates,  $anfrage, $relationen_alert;

    $select = $db->query("SELECT *
    FROM ".TABLE_PREFIX."relationen
    WHERE ok = '0'
    AND angefragte = '".$mybb->user['uid']."'
     ");

    $row = $db->fetch_array($select);
    $count = mysqli_num_rows ($select);
    if($count == '1'){
        $anfrage = "Anfrage";
    } else {
        $anfrage = "Anfragen";
    }
    if($count != 0){
        eval("\$relationen_alert = \"" . $templates->get ("relationen_alert") . "\";");
    }

}

function misc_relation(){

    global $mybb, $templates, $lang, $header, $headerinclude, $footer, $page, $db, $optionen;

    if($mybb->get_input('action') == 'relationen')
    {

//Erstmal die Anzeige generieren
        add_breadcrumb('Relationsanfragen', "misc.php?action=relationen");

        //usernameID ziehen
        $uid = $mybb->user['uid'];

        //PMhandler starten
        require_once MYBB_ROOT."inc/datahandlers/pm.php";
        $pmhandler = new PMDataHandler();


//ab geht es mit der Abfrage. Hier ist der Part für die erhaltenen Anfragen
        $select = $db->query("SELECT *
        FROM ".TABLE_PREFIX."relationen r
        LEFT JOIN ".TABLE_PREFIX."users u
        ON r.anfrager = u.uid
        WHERE r.ok = '0'
        AND r.angefragte = '".$uid."'
        ORDER BY u.username
        ");

        while($row = $db->fetch_array ($select)){
            $username = format_name($row['username'], $row['usergroup'], $row['displaygroup']);
            $user= build_profile_link($username, $row['uid']);
            $optionen = "<a href='misc.php?action=relationen&del=$row[rid]'><i class=\"fa fa-times\" aria-hidden=\"true\"></i></a> <a href='misc.php?action=relationen&ok=$row[rid]'><i class=\"fa fa-check\" aria-hidden=\"true\"></i></a>";

            if($row['kat'] == 'familie'){
                $row['kat'] = "Familie";
            } elseif($row['kat'] == 'freunde'){
                $row['kat'] = "Freunde";
            } elseif($row['kat'] == 'bekannte'){
                $row['kat'] = "Bekannte";
            } elseif($row['kat'] == 'liebe'){
                $row['kat'] = "Liebe";
            }elseif($row['kat'] == 'feinde'){
                $row['kat'] = "Feinde";
            }elseif($row['kat'] == 'vergangen'){
                $row['kat'] = "Vergangenheit";
            }

            eval("\$anfragen_bit .= \"" . $templates->get ("relationen_anfragen_bit") . "\";");
        }

        //und hier noch die eigenen Anfragen (auch hier löschen möglich)
        $select = $db->query("SELECT *
        FROM ".TABLE_PREFIX."relationen r
            LEFT JOIN ".TABLE_PREFIX."users u
            ON r.angefragte = u.uid 
            WHERE r.anfrager = '".$uid."'
            AND r.ok = '0'
        ");

        while($row = $db->fetch_array($select)){
            $username = format_name($row['username'], $row['usergroup'], $row['displaygroup']);
            $user= build_profile_link($username, $row['uid']);
            $optionen = "<a href='misc.php?action=relationen&del=$row[rid]'><i class=\"fa fa-times\" aria-hidden=\"true\"></i></a> <a href='misc.php?action=relationen&ok=$row[rid]'><i class=\"fa fa-check\" aria-hidden=\"true\"></i></a>";


            if($row['kat'] == 'familie'){
                $row['kat'] = "Familie";
            } elseif($row['kat'] == 'freunde'){
                $row['kat'] = "Freunde";
            } elseif($row['kat'] == 'bekannte'){
                $row['kat'] = "Bekannte";
            } elseif($row['kat'] == 'liebe'){
                $row['kat'] = "Liebe";
            }elseif($row['kat'] == 'feinde'){
                $row['kat'] = "Feinde";
            }elseif($row['kat'] == 'vergangen'){
                $row['kat'] = "Vergangenheit";
            }

            eval("\$deine_anfragen .= \"" . $templates->get ("relationen_anfragen_bit") . "\";");
        }


        //Anfragen können natürlich gelöscht werden (wenn man sich vertan hat zum Beispiel).
        $del = $mybb->input['del'];
        if($del){
            $select = $db->query("SELECT * 
            FROM ".TABLE_PREFIX."relationen r
            LEFT JOIN ".TABLE_PREFIX."users u
            ON r.anfrager = u.uid 
            WHERE r.rid = '".$del."'
            ");

            $row = $db->fetch_array($select);
            $angefragte = $row['anfrager'];
            $anfrager = $row['angefragte'];

            $pm_change = array(
                "subject" => "Relationsanfrage abgelehnt",
                "message" => "Liebe/r {$row['username']}, <br /> Leider wurde deine Relationsanfrage abgelehnt. Gerne kannst du einen neuen Vorschlag machen und eine neue Anfrage stellen oder mich per PN Kontaktieren, so dass wir gemeinsam was ausmachen können. ",
                //to: wer muss die anfrage bestätigen
                "fromid" => $anfrager,
                //from: wer hat die anfrage gestellt
                "toid" => $angefragte
            );
            // $pmhandler->admin_override = true;
            $pmhandler->set_data ($pm_change);
            if (!$pmhandler->validate_pm ())
                return false;
            else {
                $pmhandler->insert_pm ();
            }

            $db->delete_query("relationen", "rid = '$del'");
            redirect("misc.php?action=relationen");
        }

        //und natürlich auch den Spaß annehmen
        $ok = $mybb->input['ok'];
        if($ok){
            $select = $db->query("SELECT * 
            FROM ".TABLE_PREFIX."relationen r
            LEFT JOIN ".TABLE_PREFIX."users u
            ON r.anfrager = u.uid 
            WHERE r.rid = '".$ok."'
            ");

            $row = $db->fetch_array($select);
            $angefragte = $row['anfrager'];
            $anfrager = $row['angefragte'];

            $pm_change = array(
                "subject" => "Relationsanfrage bestätigt",
                "message" => "Liebe/r {$row['username']}, <br /> ich habe deine Relationsanfrage bestätigt. Du findest den Eintrag nun in deinem Profil.",
                //to: wer muss die anfrage bestätigen
                "fromid" => $anfrager,
                //from: wer hat die anfrage gestellt
                "toid" => $angefragte
            );
            // $pmhandler->admin_override = true;
            $pmhandler->set_data ($pm_change);
            if (!$pmhandler->validate_pm ())
                return false;
            else {
                $pmhandler->insert_pm ();
            }


            $db->query("UPDATE ".TABLE_PREFIX."relationen SET ok = '1'  WHERE rid = '$ok'");
            redirect("misc.php?action=relationen");
        }
        eval("\$page = \"".$templates->get("relationen_anfragen")."\";");
        output_page($page);
    }
}
