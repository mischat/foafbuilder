#!/usr/bin/perl -w

use strict;

use Encode;

use Text::ParseWords;
use URI::Escape;
use Time::Local;
use Mysql;
use Spreadsheet::WriteExcel;

use lib '/usr/local/src/qdos/extractors/';
use settings;
use sparql;

# this selects the day we'll examine the logs for
my $ztime = $ARGV[0];
my ($zy, $zm, $zd) = split(/-/,$ARGV[0]);
my $logdaytime = timegm(0, 0, 0, $zd, $zm - 1, $zy);

sub unescape {
  my($string) = decode('UTF-8', uri_unescape(@_));
  $string =~ tr/+/ /d;
  return $string;
}

open (BRIEF, ">:utf8", "/tmp/brief_daily");

my $excel = Spreadsheet::WriteExcel->new("/tmp/qdossearch.xls");

my $xl_bold = $excel->add_format();
$xl_bold->set_bold();
my $xl_iso_format = $excel->add_format();
$xl_iso_format->set_num_format('YYYY-MM-DD');
my $xl_percent = $excel->add_format();
$xl_percent->set_num_format('0%');

my %visitor; # Hits for each individual unique visitor
my %celeb_unique; # Number of unique visitors looking at this celebrities profile
my %celeb_sessions; # Which sessions have viewed this celebrity profile
my %track_unique; # Number of unique visitors sent to this profile by our adverts
my %track_sessions; # Which sessions have be sent by our adverts
my %track_hits; # Raw number of hits to the profile from adverts
my %search_unique; # Number of unique visitors searching for this name
my %search_sessions; # Which sessions have searched for this name
my %referers_unique; # Number of unique visitors referred by a particular domain
my %referers_sessions; # Which sessions have been referred by that domain
my %googles_unique; # Number of unique visitors who Googled for a particular thing
my %googles_sessions; # Which sessions have Googled for it

my %miniimg; # Minimg hits by domain
my %adverts; # Adverts bring people to us
my %qed; # people who visit Q-ED page

my %seo; # referrals from search engines, for SEO

# they are all enumerated explicitly in the database schema anyway, so...
# without this the INSERT query will fail if one engine produced zero visitors
$seo{'google'} = 0;
$seo{'ask'} = 0;
$seo{'yahoo'} = 0;
$seo{'msn'} = 0;
$seo{'live'} = 0;
$seo{'aol'} = 0;

my %searches; # Count of searches by session ID
my %start; # When this session ID was first seen
my %journey; # Length of time this session ID was seen in use
my %typedit;  # Which sessions have apparently just typed in our front page URL

my $hits = 0; # people who typed our URL in or have referer disabled
my $frontpage = 0; # hits on the front page
my $qins = 0; # QED Adverts bring students to us

my ($sec,$min,$hour,$mday,$mon,$year) = localtime($logdaytime);
my @abbr = qw( Jan Feb Mar Apr May Jun Jul Aug Sep Oct Nov Dec );
my $today = sprintf("%02d/%s/%d", $mday, $abbr[$mon], $year + 1900);
my $iso_date = sprintf("%4d-%02d-%02d", $year + 1900, $mon + 1, $mday);

print BRIEF "Brief QDOS report for $iso_date\r\n\r\n";

while (<STDIN>) {
   chomp;
   my ($address, $remote, $username, $timestamp, $zone, $request, $code, $length, $referer, $agent, $session) = parse_line(" ", 0, $_);
   $timestamp = substr($timestamp, 1);
   $zone = chop($zone);

   if (not $timestamp =~ /$today/ ) {
     next;
   }

   $hits++;

   # ignore some types of robot
   if ($agent =~ /^msnbot/ ) { next; }
   if ($agent =~ /^AdsBot-Google/ ) { next; }
   if ($agent =~ /^Mozilla\/5\.0 \(compatible; Googlebot/ ) { next; }
   if ($agent =~ /^Mozilla\/5\.0 \(compatible; Yahoo! Slurp/ ) { next; }

   if ($address eq '193.203.246.177') { next; } # Garlik office
   if ($address eq '82.69.171.90') { next; } # Nick's laptop

   if (not $session) {
     $session = "-"; # this is default for new log format anyway
   }

   my ($h,$m,$s) = split(/:/,substr($timestamp, 12));
   my $time_t = timelocal($s, $m, $h, 1, 1, 1);

   if ($visitor{$session}++ == 0) {
     $start{$session} = $time_t;
   }
   $journey{$session} = $time_t - $start{$session};

   if ($request =~ /^GET \/in\/(.*) HTTP\/1\.[01]$/) {
     my $in = $1;
     $adverts{$in}++;
   } elsif ($request =~ /^GET \/qed /) {
     $qed{$session}++;
   } elsif ($request =~ /^GET \/qin\/(.*) HTTP\/1\.[01]$/) {
     $qins++;
   }

   if ($code == '302') { next; } # 302 redirect

   if ($request =~ /search.*[?&]searchname=([^& ]+)/) {
     $searches{$session}++;
     my $name = lc($1);
     my $sid = $session . '::' . $name;
     if (defined($search_sessions{$sid})) {
       $search_sessions{$sid}++;
     } else {
       $search_sessions{$sid}= 1;
       $search_unique{$name}++;
     }
   }

   if ($request =~ /^GET \/ /) {
     $frontpage++;
     if ($referer eq '-') {
       $typedit{$session}++;
     }
   }

   if ($request =~ /^GET \/celeb.*\/([0-9a-zA-Z]{32})(\/html|\/track)?/) {
     my $uri = "http://qdos.com/celeb/" . $1;
     my $sid = $uri . '/' . $session;
     if (defined($celeb_sessions{$sid})) {
       $celeb_sessions{$sid}++;
     } else {
       $celeb_sessions{$sid}= 1;
       $celeb_unique{$uri}++;
     }
   }

   if ($request =~ /^GET \/celeb\/([0-9a-zA-Z]{32})\/track/) {
     my $uri = "http://qdos.com/celeb/" . $1;
     my $sid = $uri . '/' . $session;
     $track_hits{$uri}++;
     if (defined($track_sessions{$sid})) {
       $track_sessions{$sid}++;
     } else {
       $track_sessions{$sid}= 1;
       $track_unique{$uri}++;
     }

   }

   # at this point we don't care about referrals to images
   if ($request =~ /^GET \/images\//) { next; }

   # this could be merged with the structure below to be smarter
   if ($referer =~ /search.*[?&]q(uery)?=([^&]+)/) {
     my $query = $2;
     my $sid = $session . '::' . $query;
     if (defined($googles_sessions{$sid})) {
       $googles_sessions{$sid}++;
     } else {
       $googles_sessions{$sid}= 1;
       $googles_unique{$query}++;
     }
   }
   if ($referer =~ /^http:\/\/qdos.com\/qed$/) {
     $qed{$session}++;
   } elsif ($referer =~ /^https?:\/\/([a-z.0-9-]*)\//) {
     my $domain = $1;
     if ($domain =~ /www\.google\.[a-z.]+$/) {
       $seo{'google'}++;
     } elsif ($domain =~ /ask\.com$/) {
       $seo{'ask'}++;
     } elsif ($domain =~ /search\.yahoo\.com$/) {
       $seo{'yahoo'}++;
     } elsif ($domain =~ /search\.msn\.co/) {
       $seo{'msn'}++;
     } elsif ($domain =~ /search\.live\.com$/) {
       $seo{'live'}++;
     } elsif ($domain =~ /search\.aol\./) {
       $seo{'aol'}++;
     }

     # special mangling for Google here
     $domain =~ s/google\.[a-z]+$/google.*/;
     $domain =~ s/google\.com?\.[a-z]+$/google.*/;
     if ($domain ne 'qdos.com' && $domain ne 'www.qdos.com') {
       if ($request =~ /^GET \/(user|celeb)\/([0-9a-zA-Z]{32})\/miniimg/) {
         $miniimg{$domain}++;
       } else {
         my $sid = $session . '::' . $domain;
	 if (defined($referers_sessions{$sid})) {
           $referers_sessions{$sid}++;
         } else {
           $referers_sessions{$sid}= 1;
           $referers_unique{$domain}++;
         }
       }
     }
   }
}

my $dbh = Mysql->connect("db.qdos.com", "qdos", "garlik", "");
my $requested; my $sent; my $used;

{
  my $result = $dbh->query("SELECT COUNT(*) AS requested FROM invites WHERE requested LIKE '$iso_date%'");
  my %ret = $result->fetchhash();
  $requested = $ret{'requested'};
}

{
  my $result = $dbh->query("SELECT COUNT(*) AS sent FROM invites WHERE sent LIKE '$iso_date%'");
  my %ret = $result->fetchhash();
  $sent = $ret{'sent'};
}

{
  my $result = $dbh->query("SELECT COUNT(*) AS used FROM users WHERE created LIKE '$iso_date%'");
  my %ret = $result->fetchhash();
  $used = $ret{'used'};
}

{
  my $result = $dbh->query("SELECT COUNT(*) AS signups FROM invites");
  my %ret = $result->fetchhash();
  my $signups = "(unknown)";
  if ($ret{'signups'}) {
    $signups = $ret{'signups'};
  }
  $result = $dbh->query("SELECT COUNT(*) AS signups FROM users WHERE created > '2008-03-28'");
  %ret = $result->fetchhash();
  if ($ret{'signups'}) {
    $signups += $ret{'signups'};
  }
  print BRIEF "Cumulative beta signups (since launch)   $signups\r\n\r\n";
}

my $unique = keys (%visitor) ;
print BRIEF "Daily unique visitors   $unique\r\n\r\n";

my $ten_percent = 0;
my $fifty_percent = 0;
my $i = 0;
foreach my $key (sort {$journey{$b} <=> $journey{$a} } keys %visitor) {
  $i++;
  if ($i == int($unique / 10)) {
     $ten_percent = $journey{$key} / 60;
  }
  if ($i == int($unique / 2)) {
     $fifty_percent = $journey{$key} / 60;
  }
}

my $max = 0; my $total = 0; my $count = keys (%searches);
my $median = 0; my $max_session;

$i = 0;
foreach my $key (sort {$searches{$b} <=> $searches{$a} } keys %searches) {
  if ($key eq "-") {
    next;
  }
  my $s = $searches{$key};

  if ($i++ == int($count / 2)) {
    $median = $s;
  }

  if ($s > $max) {
    $max = $s;
    $max_session = $key;
  }
  $total += $s;
}
my $mean = $total / ($count ? $count : 1);

my $qed_visitors = keys (%qed);
my $qed_signups = 0;

{
  my $result = $dbh->query("SELECT COUNT(*) AS qed FROM users WHERE qed = 1 AND created LIKE '$iso_date%'");
  my %ret = $result->fetchhash();
  $qed_signups = "$ret{'qed'}";
}

if (($#ARGV > 0) && $ARGV[1] eq 'final') {
  $dbh->query("INSERT INTO webstats SET day='$iso_date', requested=$requested, sent=$sent, used=$used, visitors=$unique, hits=$hits, frontpage=$frontpage, 10pc_time=$ten_percent, 50pc_time=$fifty_percent, searchers=$count, max_searches=$max, mean_searches=$mean, median_searches=$median, qed_visitors=$qed_visitors, qed_signups=$qed_signups, qin_arrivals=$qins");
  $dbh->query("INSERT INTO searchengines SET day='$iso_date', google=$seo{google}, ask=$seo{ask}, yahoo=$seo{yahoo}, msn=$seo{msn}, live=$seo{live}, aol=$seo{aol}");

  foreach my $track (sort {$adverts{$b} <=> $adverts{$a} } keys %adverts) {
    my $count = $adverts{$track};
    $dbh->query("INSERT INTO trackstats SET day='$iso_date', track='$track', count=$count");
  }
}


{
  my $result = $dbh->query("SELECT sum(visitors) AS visitors FROM webstats");
  my %ret = $result->fetchhash();
  my $visitors = "(unknown)";
  if ($ret{'visitors'}) {
    $visitors = $ret{'visitors'};
  }
  print BRIEF "Cumulative unique visitors (since launch)   $visitors\r\n\r\n";
}

print BRIEF "Daily top 10 celebrities viewed were...\r\n\r\n";
$i = 0;
foreach my $key (sort {$celeb_unique{$b} <=> $celeb_unique{$a} } keys %celeb_unique) {
  my $name = "(unknown)";
  my $res = sparql_query("http://luma:8080/sparql/","SELECT ?name WHERE { <$key> <http://xmlns.com/foaf/0.1/name> ?name }");
  for my $row (@{$res}) {
    $name = decode('UTF-8', $row->{'name'});
  }
  print BRIEF "$celeb_unique{$key} $key $name\r\n";

  if ($i++ == 10) { last; }
}

my $summarysheet = $excel->add_worksheet('Summary');
$summarysheet->write('A1', "Summary", $xl_bold);
$summarysheet->write('A3', "Date:", $xl_bold);
$summarysheet->write('B3', "$iso_date", $xl_bold);
$summarysheet->write('A6', "Date", $xl_bold);
$summarysheet->write('B6', "Paid search", $xl_bold);
$summarysheet->write('C6', "Natural search", $xl_bold);
$summarysheet->write('D6', "Other", $xl_bold);
$summarysheet->write('E6', "Total", $xl_bold);
$summarysheet->set_column(0, 0, 18);
$summarysheet->set_column(1, 1, 17);
$summarysheet->set_column(2, 2, 20);
$summarysheet->freeze_panes(6, 0); # freeze first six rows

my %paid_total; # total by day
my %natural_total; # total by day
my %unique_total; # total by day

my $paidsheet = $excel->add_worksheet('Paid Search');
$paidsheet->write('A1', "Paid Search", $xl_bold);
$paidsheet->write('A3', "Date:", $xl_bold);
$paidsheet->write('B3', "$iso_date", $xl_bold);
$paidsheet->write('A6', "Date", $xl_bold);
$paidsheet->write('B6', "Total", $xl_bold);
$paidsheet->write('C5', "Ad Group", $xl_bold);
$paidsheet->set_column(0, 0, 18);
$paidsheet->set_column(1, 24, 10);
$paidsheet->freeze_panes(6, 0); # freeze first six rows

{
  my %trackids; # identifiers for different paid search criteria
  my $result = $dbh->query("SELECT DISTINCT track FROM trackstats ORDER BY track");
  $i = 2;
  while (my $ret = $result->fetchrow_hashref()) {
    my $track = $ret->{'track'};
    $paidsheet->write(5, $i, '.../in/' . $track, $xl_bold);
    $trackids{$track} = $i++;
  }

  $result = $dbh->query("SELECT day, track, count FROM trackstats ORDER BY day, track");
  $i = 0;
  my $olddate = '';
  while (my $ret = $result->fetchrow_hashref()) {
    my $col = $trackids{$ret->{'track'}};
    my $date = $ret->{'day'};
    if ($date ne $olddate) {
      $olddate = $date;
      $i++; # new row
      $paidsheet->write_date_time($i + 5, 0, $date . 'T', $xl_iso_format);
      # NB this only includes columns up to Z, if there are more types of track URI in use we need to increase this
      $paidsheet->write_formula($i + 5, 1, "=SUM(C" . ($i + 6) . ":Z" . ($i + 6) . ")");
      $paid_total{$date} = "='Paid Search'!B" . ($i + 6);
    }
    my $count = $ret->{'count'};
    $paidsheet->write($i + 5, $col, $count);
  }
}

#my $tracksheet = $excel->add_worksheet('Paid search celebs');
#$tracksheet->set_column(0, 0, 25);
#$tracksheet->set_column(1, 1, 25);
#$tracksheet->set_column(2, 2, 50);
#$tracksheet->set_column(3, 3, 40);
#$tracksheet->write('A1', "Tracked celeb pages", $xl_bold);
#$tracksheet->write('A3', "# of sessions", $xl_bold);
#$tracksheet->write('B3', "# of hits", $xl_bold);
#$tracksheet->write('C3', "URI", $xl_bold);
#$tracksheet->write('D3', "Celeb name", $xl_bold);
#$i = 0;
#foreach my $key (sort {$track_unique{$b} <=> $track_unique{$a} } keys %track_unique) {
#  my $name = "(unknown)";
#  my $res = sparql_query("http://luma:8080/sparql/","SELECT ?name WHERE { <$key> <http://xmlns.com/foaf/0.1/name> ?name }");
#  for my $row (@{$res}) {
#    $name = decode('UTF-8', $row->{'name'});
#  }
#  $tracksheet->write_number($i + 3, 0, $track_unique{$key});
#  $tracksheet->write_number($i + 3, 1, $track_hits{$key});
#  $tracksheet->write_url($i + 3, 2, $key, $key);
#  $tracksheet->write_string($i + 3, 3, $name);
#
#  if (++$i >= 32000) { last; }
#}

my $seosheet = $excel->add_worksheet('Natural Search');
$seosheet->set_column(0, 0, 18);
$seosheet->write('A1', "Natural Search", $xl_bold);
$seosheet->write('A2', "note that non-search sites from the same companies are not included e.g. MSN Mail, Google translator");
$seosheet->write('A3', "Date: ", $xl_bold);
$seosheet->write('B3', "$iso_date", $xl_bold);

$seosheet->write('B5', "Search Engine", $xl_bold);
$seosheet->write('A6', "Date", $xl_bold);
$seosheet->write('B6', "Total", $xl_bold);
$seosheet->write('C6', "Google", $xl_bold);
$seosheet->write('D6', "Ask.com", $xl_bold);
$seosheet->write('E6', "Yahoo", $xl_bold);
$seosheet->write('F6', "MSN", $xl_bold);
$seosheet->write('G6', "Live.com", $xl_bold);
$seosheet->write('H6', "AOL", $xl_bold);
$seosheet->freeze_panes(6, 0); # freeze first six rows

$i = 0;
{   
  my $result = $dbh->query("SELECT * FROM searchengines ORDER BY day");
  while (my @ret = $result->fetchrow_array()) {
    my $c = 0;
    foreach my $item (@ret) {
      if ($c == 0) {
         $seosheet->write_date_time($i + 6, $c++, $item . 'T', $xl_iso_format);
         $seosheet->write_formula($i + 6, $c++, "=SUM(C" . ($i + 7) . ":H" . ($i + 7) . ")");
         $natural_total{$item} = "='Natural Search'!B" . ($i + 7);
      } else {
         $seosheet->write($i + 6, $c++, $item);
      }
    }
    $i++;
  }
}

my $referralsheet = $excel->add_worksheet('Referrals');
$referralsheet->write('A1',"Referrals", $xl_bold);
$referralsheet->write('A3', "Date:", $xl_bold);
$referralsheet->write('B3', "$iso_date", $xl_bold);

$referralsheet->write('A6', "Unique visitors", $xl_bold);
$referralsheet->set_column(0, 0, 20);
$referralsheet->write('B6', "Referred from site", $xl_bold);
$referralsheet->set_column(1, 1, 50);
my $no_referrers = keys(%typedit);
$referralsheet->write('A7', $no_referrers);
$referralsheet->write('B7', "(no referrer)");
$i = 0;
foreach my $key (sort {$referers_unique{$b} <=> $referers_unique{$a} } keys %referers_unique) {
  $referralsheet->write($i + 7, 0, $referers_unique{$key});
  $referralsheet->write_url($i + 7, 1, "http://$key/", $key);
  $i++;
}

$referralsheet->write('D6', "Mini QDOS images seen", $xl_bold);
$i = 0;
foreach my $key (sort {$miniimg{$b} <=> $miniimg{$a} } keys %miniimg) {
  $referralsheet->write($i + 7, 3, $miniimg{$key});
  $referralsheet->write_url($i + 7, 4, "http://$key/", $key);
  $i++;
}

my $activitysheet = $excel->add_worksheet('Website Activity');
$activitysheet->write('A1', "Website Activity", $xl_bold);
$activitysheet->write('A3', "Date:", $xl_bold);
$activitysheet->write('B3', "$iso_date", $xl_bold);

$activitysheet->set_column(0, 0, 17);
$activitysheet->write('A6', "Date", $xl_bold);
$activitysheet->set_column(1, 12, 12);
$activitysheet->write('B6', "Beta Signups", $xl_bold);
$activitysheet->write('C6', "Invites Sent", $xl_bold);
$activitysheet->write('D6', "Accounts created", $xl_bold);
 $activitysheet->write('E6', "Visitor:beta conversion", $xl_bold);
$activitysheet->write('F6', "Unique Visitors", $xl_bold);
$activitysheet->write('G6', "Hits", $xl_bold);
$activitysheet->write('H6', "Frontpage views", $xl_bold);
$activitysheet->write('I5', "Time",  $xl_bold);
$activitysheet->write('I6', "\(10th percentile\)", $xl_bold);
$activitysheet->write('J5', "Time", $xl_bold);
$activitysheet->write('J6', "\(50th percentile\)", $xl_bold);
$activitysheet->write('K6', "# users who searched", $xl_bold);
 $activitysheet->write('L6', "% unique search", $xl_bold);
$activitysheet->write('M5', "Most searches", $xl_bold);
$activitysheet->write('M6', "by one user", $xl_bold);
$activitysheet->write('N5', "Mean (average)", $xl_bold);
$activitysheet->write('N6', "searches per user", $xl_bold);
$activitysheet->write('O5', "Median", $xl_bold);
$activitysheet->write('O6', "searches per user", $xl_bold);
$activitysheet->write('P6', "QED Visitors", $xl_bold);
$activitysheet->write('Q6', "QED Signups", $xl_bold);
$activitysheet->write('R5', "QED Arrivals", $xl_bold);
$activitysheet->write('R6', "from campaign", $xl_bold);
$activitysheet->freeze_panes(6, 0); # freeze first six rows

$i = 0;
{
  my $result = $dbh->query("SELECT * FROM webstats ORDER BY day");
  while (my @ret = $result->fetchrow_array()) {
    my $c = 0;
    foreach my $item (@ret) {
      if ($c == 4) {
         $activitysheet->write_formula($i + 6, $c++, "=B" . ($i + 7) . "/F" . ($i + 7), $xl_percent);
      }
      if ($c == 11) {
         $activitysheet->write_formula($i + 6, $c++, "=K" . ($i + 7) . "/F" . ($i + 7), $xl_percent);
      }
      if ($c == 0) {
         $activitysheet->write_date_time($i + 6, $c++, $item . 'T', $xl_iso_format);
         $unique_total{$item} = "='Website Activity'!F" . ($i + 7);
      } else {
         $activitysheet->write($i + 6, $c++, $item);
      }
    }
    $i++;
  }
}
$activitysheet->set_selection($i + 3, 0);

my $celebsheet = $excel->add_worksheet('Celebrities');
$celebsheet->set_column(0, 0, 20);
$celebsheet->set_column(1, 1, 50);
$celebsheet->set_column(2, 2, 40);
$celebsheet->write('A1', "Most viewed celebrities", $xl_bold);
$celebsheet->write('A3', "Date:", $xl_bold);
$celebsheet->write('B3', "$iso_date", $xl_bold);
$celebsheet->write('A6', "Unique visitors", $xl_bold);
$celebsheet->write('B6', "URI", $xl_bold);
$celebsheet->write('C6', "Celeb name", $xl_bold);

$i = 0;
foreach my $key (sort {$celeb_unique{$b} <=> $celeb_unique{$a} } keys %celeb_unique) {
  my $name = "(unknown)";
  my $res = sparql_query("http://luma:8080/sparql/","SELECT ?name WHERE { <$key> <http://xmlns.com/foaf/0.1/name> ?name }");
  for my $row (@{$res}) {
    $name = decode('UTF-8', $row->{'name'});
  }
  $celebsheet->write_number($i + 6, 0, $celeb_unique{$key});
  $celebsheet->write_url($i + 6, 1, $key, $key);
  $celebsheet->write_string($i + 6, 2, $name);

  if (++$i >= 100) { last; }
}

my $keywordsheet = $excel->add_worksheet('Keyword Searches');
$keywordsheet->set_column(0, 0, 20);
$keywordsheet->write('A1', "Keyword Searches", $xl_bold);
$keywordsheet->write('A3', "Date:", $xl_bold);
$keywordsheet->write('B3', "$iso_date", $xl_bold);
$keywordsheet->write('A6', "Unique visitors", $xl_bold);
$keywordsheet->write('B6', "Search engine query", $xl_bold);

$i = 0;
foreach my $key (sort {$googles_unique{$b} <=> $googles_unique{$a} } keys %googles_unique) {
  $keywordsheet->write($i + 6, 0, $googles_unique{$key});
  $keywordsheet->write($i + 6, 1, unescape($key));
  $i++;
}

#my $namesheet = $excel->add_worksheet('QDOS Name Searches');
#$namesheet->set_column(0, 0, 20);
#$namesheet->set_column(1, 1, 30);
#$namesheet->write('A1', "QDOS Name Searches", $xl_bold);
#$namesheet->write('A3', "Date:", $xl_bold);
#$namesheet->write('B3', "$iso_date", $xl_bold);
#$namesheet->write('A6', "Unique visitors", $xl_bold);
#$namesheet->write('B6', "Name searched for", $xl_bold);
#$i = 0;
#foreach my $key (sort {$search_unique{$b} <=> $search_unique{$a} } keys %search_unique) {
#  $namesheet->write_number($i + 6, 0, $search_unique{$key});
#  $namesheet->write_string($i + 6, 1, unescape($key));
#
#  if (++$i >= 1000) { last; }
#
#}

# fill out summary

{
  my $result = $dbh->query("SELECT DISTINCT day FROM trackstats ORDER BY day");
  $i = 0;
  while (my $ret = $result->fetchrow_hashref()) {
    my $date = $ret->{'day'};
    $summarysheet->write_date_time($i + 6, 0, $date . 'T', $xl_iso_format);
    $summarysheet->write_formula($i + 6, 1, $paid_total{$date}) if defined($paid_total{$date});
    $summarysheet->write_formula($i + 6, 2, $natural_total{$date}) if defined($natural_total{$date});
    $summarysheet->write_formula($i + 6, 4, $unique_total{$date}) if defined($unique_total{$date});
    $i++;
  }
}

