<?php


$dirData = '../padly-server/data/';

$fileUrls = array(
    'active_skills.txt' => 'https://www.padherder.com/api/active_skills/',
    'awakenings.txt' => 'https://www.padherder.com/api/awakenings/',
    'evolutions.txt' => 'https://www.padherder.com/api/evolutions/',
    'leader_skills.txt' => 'https://www.padherder.com/api/leader_skills/',
    'materials.txt' => 'https://www.padherder.com/api/materials/',
    'monsters.txt' => 'https://www.padherder.com/api/monsters/',
    'skillups.txt' => 'https://www.padherder.com/api/food/'
);

$monsters = false;

foreach ($fileUrls as $filename => $url) {
    $options = array(
        CURLOPT_HEADER => 0,
        CURLOPT_FRESH_CONNECT => 1, 
        CURLOPT_RETURNTRANSFER => 1, 
        CURLOPT_URL => $url,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',
    );

    echo "downloading $url".PHP_EOL;
    $ch = curl_init(); 
    curl_setopt_array($ch, $options); 
    if( !$result = curl_exec($ch)) {
        trigger_error(curl_error($ch)); 
    } 
    curl_close($ch); 

    $result = json_decode($result, true);
    if ($filename == 'monsters.txt') {
        $monsters = $result;
    }

    $result = json_encode($result, JSON_PRETTY_PRINT);
    file_put_contents($dirData.$filename, $result);
}

$images = array();
$dirImages = "../padly-server/public/images/";
$pherderUrl = "https://www.padherder.com";
$pdxImgUrl = "http://www.puzzledragonx.com/en/img/monster/";

foreach ($monsters as $m) {
    $id = $m['id'];
    $images["$id.60x60.png"] = $pherderUrl.$m["image60_href"];
    $images["$id.40x40.png"] = $pherderUrl.$m["image40_href"];
    $images["$id.full.jpg"] = $pdxImgUrl."MONS_$id.jpg";
}

foreach ($images as $filename => $url) {
    $options = array(
        CURLOPT_HEADER => 0,
        CURLOPT_BINARYTRANSFER => 1,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',
    );

    $filename = $dirImages.$filename;
    if(file_exists($filename)){
        echo "skipping $url".PHP_EOL;
        continue;  // don't download images that we already have
    }
    echo "downloading $url".PHP_EOL;
    
    $ch = curl_init ($url);
    curl_setopt_array($ch, $options);
    $raw=curl_exec($ch);
    curl_close ($ch);
    $fp = fopen($filename,'x');
    fwrite($fp, $raw);
    fclose($fp);
}


die(1);

$mtg = new MtgCards;
$mtg->load_from_file('../dbdata/rtr.txt','RTR');
$mtg->load_from_file('../dbdata/gtc.txt','GTC');
$s = $mtg->getCards ();
MongoLoader::upload($s, true);



class MongoLoader {

    const DBNAME = 'mtg';
    const CONN_STR = 'localhost';

    public static function upload ($data_set, $clean) {
        try {
            $m = new Mongo(self::CONN_STR);
            $db = $m->selectDB(self::DBNAME);

            if ($clean)
                $db->cards->remove();

            $cnt = 0;
            foreach ($data_set as $card) {
                $db->cards->insert ($card);
                $cnt++;
                echo "Inserted card {$card['name']} with ID: {$card['_id']}".PHP_EOL;
            }
            echo "$cnt cards loaded".PHP_EOL;
            $m->close();
        }
        catch (MongoConnectionException $e) {
            echo 'Couldn\'t connect to mongodb, is the "mongo" process running?'.PHP_EOL;
            return false;
        } 
        catch (MongoException $e) {
            echo ('Error: ' . $e->getMessage() . PHP_EOL);
            return false;
        }
        return true;
    }
}


class MtgCards {

    public $dbfield_map = array (
        "Card Name"    => "name",
        "Card Color"   => "color",
        "Mana Cost"    => "mana",
        "Type & Class" => "type",
        "Pow"          => "power",
        "Tou"          => "toughness",
        "Card Text"    => "rule_text",
        "Flavor Text"  => "flavor",
        "Artist"       => "artist",
        "Rarity"       => "rarity",
        "Card #"       => "card_no"
    );

    protected $mtg_set = array();

    function load_from_file ($filename, $set_name) {
        $f = fopen ($filename, 'rt');
        $in = '';
        $card = array ();
        $data = array ();

        while (($in = fgets ($f, 4096)) !== false) {
            $data = explode (":", $in,  2);

            $card[$data[0]] = trim($data[1]);

            if($data[0] == "Card #") {
                $this->add_card ($card, $set_name);
                $card = array();
            }
        }
        fclose($f);
    }

    function add_card ($card, $set_name) {
        $data = array ();
        $new_card = array ();

        foreach ($card as $key => $value) {
            if (empty($value))
                continue;

            if ($key == "Pow/Tou") {
                $data = explode("/", $value);
                $new_card['power'] = $data[0];
                $new_card['toughness'] = $data[1];
            }
            else if ($key == "Card #") {
                $data = explode("/", $value);
                $new_card[$this->dbfield_map[$key]] = $data[0];
            }
            else if (!empty($this->dbfield_map[$key])) {
                $new_card[$this->dbfield_map[$key]] = $value;
            }
        }
        $new_card['set'] = $set_name;
        $this->mtg_set[] = $new_card;
    }

    function getCards () {
        return ($this->mtg_set);
    }
}



?>

