<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RSSReader extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rss:fetch {--group=star5}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected $group;
    protected $body;
    protected $index;
    protected $date;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $group = $this->option('group','star5');
        if(empty($group)) return $this->error("group is ");
        $this->group = $group;
        $sites = config('opml.'.$this->group);
        if(empty($sites)) return $this->error("rss sites is empty ");
        $this->date = new \Carbon\Carbon();
        foreach($sites as $site){
            $this->info("--- $site ---");
            $this->fetchSite($site);
        }
        $this->output();
    }

    protected function fetchSite($site){
        $feed = new \SimplePie();
        $feed->force_feed(true);
        $feed->set_item_limit(20);
        $feed->set_feed_url($site);
        $feed->enable_cache(false);
        $feed->set_output_encoding('utf-8');
        $feed->init();
        foreach($feed->get_items() as $item){
           $this->outputItem([
              'site'  => $site,
              'title' => $item->get_title(),
              'link'  => $item->get_permalink(),
              'date'  => new \Carbon\Carbon($item->get_date()),
              'content' => $item->get_content(),
            ]);
        }
    }

    protected function outputItem($item){
        $anchor = time()."X".rand();
        $this->index.='<li><a href="#Body'.$anchor.'" name="Title'.$anchor.'">'.$item['title'].'</a></li>';
        $this->body.='<div class="rss-item"><h1><a name="Body'.$anchor.'" href="'.$item['link'].'">'.$item['title'].'</a> @ <a href="#Title'.$anchor.'">'.$item['date']->toDateTimeString().'</a></h1>';
        $this->body.='<div class="rss-body">'.$item['content'].'</div>';
    }
    protected function output(){
        $html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>'.$this->group.' @ '.$this->date->toDateTimeString().'</title>
</head><body>
<ol>'.$this->index.'</ol>
<hr>
'.$this->body.'
</body></html>';
        $file = $this->date->formatLocalized('%Y%m%d').'.html';
        file_put_contents($file, $html);
    }

}
