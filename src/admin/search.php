<?php
include_once '../Class/autoload.php';
include_once '../config.php';

$settings = new Gratbrav\Torrentbug\Settings();

include_once '../functions.php';

if (! IsAdmin()) {
    // the user probably hit this page direct
    $options = [
        'user_id' => $cfg['user'],
        'file' => $_SERVER['PHP_SELF'],
        'action' => $cfg["constants"]["access_denied"]
    ];
    $log = new \Gratbrav\Torrentbug\Log\Service();
    $log->save($options);
    
    header("location: ../index.php");
}

$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
$searchEngine = filter_input(INPUT_GET, 'searchEngine', FILTER_SANITIZE_STRING);

if ($action == 'update_settings') {
    foreach ($_POST as $key => $value) {
        if ($key != "searchEngine") {
            $options[$key] = $value;
        }
    }
    
    $settings->save($options);
    
    $options = [
        'user_id' => $cfg['user'],
        'file' => ' Updating TorrentFlux Search Settings',
        'action' => $cfg["constants"]["admin"]
    ];
    $log = new \Gratbrav\Torrentbug\Log\Service();
    $log->save($options);
    
    $searchEngine = getRequestVar('searchEngine');
    if (empty($searchEngine))
        $searchEngine = $settings->get('searchEngine');
    header("location: search.php?searchEngine=" . $searchEngine);
    exit();
}

$subMenu = 'admin';
include_once '../header.php';

include_once '../AliasFile.php';
include_once '../RunningTorrent.php';
include_once '../searchEngines/SearchEngineBase.php';

?>

<div class="container">
    <div class="row">
        <div class="col-sm-12 bd-example">
            <form name="theForm" action="search.php" method="post">
                <table class="table table-striped">
                    <tr>
                        <th colspan="2"><img
                            src="../images/properties.png" alt="">&nbsp;&nbsp;
                            Search Settings</th>
                    </tr>
                    <tr>
                        <td>Select Search Engine</td>
                        <td>
							<?php
    $searchEngine = getRequestVar('searchEngine');
    if (empty($searchEngine))
        $searchEngine = $settings->get('searchEngine');
    echo buildSearchEngineDDL($searchEngine, true)?>
						</td>
                    </tr>
                </table>
            </form>
        </div>
    </div>
</div>


<div class="container">
    <div class="row">
        <div class="col-sm-12 bd-example">
            <form name="theSearchEngineSettings"
                action="search.php?op=updateSearchSettings"
                method="post">
                <input type="hidden" name="action"
                    value="update_settings"> <input type="hidden"
                    name="searchEngine"
                    value="<?php echo $searchEngine ?>">

                <table class="table table-striped">
                    <tr>
                        <th colspan="2">
							<?php
    if (is_file('../searchEngines/' . $searchEngine . 'Engine.php')) {
        include_once ('../searchEngines/' . $searchEngine . 'Engine.php');
        $sEngine = new SearchEngine(serialize($cfg));
        if ($sEngine->initialized) {
            ?>
            							<img src="../images/properties.png" alt="">&nbsp;&nbsp;
            							<?php echo $sEngine->mainTitle ?> Search Settings
            			</th>
                    </tr>
                    <tr>
                        <td style="width: 350"><strong>Search Engine
                                URL:</strong></td>
                        <td>
			                <?php echo "<a href=\"http://".$sEngine->mainURL."\" target=\"_blank\">".$sEngine->mainTitle."</a>"; ?>
			            </td>
                    </tr>
                    <tr>
                        <td><strong>Search Module Author:</strong></td>
                        <td>
			                <?php echo $sEngine->author; ?>
			            </td>
                    </tr>
                    <tr>
                        <td><strong>Version:</strong></td>
                        <td>
			                <?php echo $sEngine->version; ?>
			            </td>
                    </tr>
					<?php if(strlen($sEngine->updateURL)>0) { ?>
				        <tr>
                        <td><strong>Update Location:</strong></td>
                        <td>
				                <?php echo "<a href=\"".$sEngine->updateURL."\" target=\"_blank\">Check for Update</a>"; ?>
				            </td>
                    </tr>
					<?php
            }
            
            if (! $sEngine->catFilterName == '') {
                ?>
				        <tr>
                        <td><strong>Search Filter:</strong><br> Select
                            the items that you DO NOT want to show in
                            the torrent search:</td>
                        <td>
							<?php
                echo "<select multiple name=\"" . $sEngine->catFilterName . "[]\" size=\"8\" style=\"width: 125px\">";
                echo "<option value=\"-1\">[NO FILTER]</option>";
                foreach ($sEngine->getMainCategories(false) as $mainId => $mainName) {
                    echo "<option value=\"" . $mainId . "\" ";
                    if (@in_array($mainId, $sEngine->catFilter))
				                    {
				                        echo " selected";
				                    }
				                    echo ">".$mainName."</option>";
				                }
				                echo "</select>";
				                ?>
							</td>
                    </tr>
	            <?php }
			        }
				} 
			?>

			</table>

                <div style="text-align: center">
                    <input type="Submit" value="Update Settings"
                        class="btn btn-primary">
                </div>
            </form>
        </div>
    </div>
</div>
