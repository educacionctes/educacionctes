<?php
require '../options.php';
require '../views/header.php';

?>

<div class="wrap">
    <?php

    // FUNCTIONS TO MAKE THE MAGIC HAPPEN, BEST TO LEAVE THESE ALONE
    function clean_title($title)
    {
        return ucwords( str_replace( array("-", "_"), " ", $title) );
    }

    function ext($filename)
    {
        return substr( strrchr( $filename,'.' ),1 );
    }

    function display_size($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . '<span class="fs-0-8 bold">' . $units[$pow] . "</span>";
    }

    function count_dir_files( $dir)
    {
        $fi = new FilesystemIterator(__DIR__ . "/" . $dir, FilesystemIterator::SKIP_DOTS);
        return iterator_count($fi);
    }

    function get_directory_size($path)
    {
        $bytestotal = 0;
        $path = realpath($path);
        if($path!==false && $path!='' && file_exists($path))
        {
            foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object)
            {
                $bytestotal += $object->getSize();
            }
        }

        return display_size($bytestotal);
    }


    // SHOW THE MEDIA BLOCK
    function display_block( $file )
    {
        global $ignore_file_list, $ignore_ext_list, $force_download;

        $file_ext = ext($file);
        if( !$file_ext AND is_dir($file)) $file_ext = "dir";
        if(in_array($file, $ignore_file_list)) return;
        if(in_array($file_ext, $ignore_ext_list)) return;

        $download_att = ($force_download AND $file_ext != "dir" ) ? " download='" . basename($file) . "'" : "";

        $rtn = "<div class=\"block\">";
        $rtn .= "<a href=\"$file\" class=\"$file_ext\"{$download_att}>";
        $rtn .= "	<div class=\"img $file_ext\"></div>";
        $rtn .= "	<div class=\"name\">";

        if ($file_ext === "dir")
        {
            $rtn .= "		<div class=\"file fs-1-2 bold\">" . basename($file) . "</div>";
            $rtn .= "		<div class=\"data upper size fs-0-7\"><span class=\"bold\">" . count_dir_files($file) . "</span> archivos</div>";

        }
        else
        {
            $rtn .= "		<div class=\"file fs-1-2 bold\">" . basename($file) . "</div>";
            $rtn .= "		<div class=\"data upper size fs-0-7\"><span class=\"bold\">Tamaño:</span> " . display_size(filesize($file)) . "</div>";
        }

        $rtn .= "	</div>";
        $rtn .= "	</a>";
        $rtn .= "</div>";
        return $rtn;
    }


    // RECURSIVE FUNCTION TO BUILD THE BLOCKS
    function build_blocks( $items, $folder )
    {
        global $ignore_file_list, $ignore_ext_list, $sort_by, $toggle_sub_folders, $ignore_empty_folders;

        $objects = array();
        $objects['directories'] = array();
        $objects['files'] = array();

        foreach($items as $c => $item)
        {
            if( $item == ".." OR $item == ".") continue;

            // IGNORE FILE
            if(in_array($item, $ignore_file_list)) { continue; }

            if( $folder && $item )
            {
                $item = "$folder/$item";
            }

            $file_ext = ext($item);

            // IGNORE EXT
            if(in_array($file_ext, $ignore_ext_list)) { continue; }

            // DIRECTORIES
            if( is_dir($item) )
            {
                $objects['directories'][] = $item;
                continue;
            }

            // FILE DATE
            $file_time = date("U", filemtime($item));

            // FILES
            if( $item )
            {
                $objects['files'][$file_time . "-" . $item] = $item;
            }
        }

        foreach($objects['directories'] as $c => $file)
        {
            $sub_items = (array) scandir( $file );

            if( $ignore_empty_folders )
            {
                $has_sub_items = false;
                foreach( $sub_items as $sub_item )
                {
                    $sub_fileExt = ext( $sub_item );
                    if( $sub_item == ".." OR $sub_item == ".") continue;
                    if(in_array($sub_item, $ignore_file_list)) continue;
                    if(in_array($sub_fileExt, $ignore_ext_list)) continue;

                    $has_sub_items = true;
                    break;
                }

                if( $has_sub_items ) echo display_block( $file );
            }
            else
            {
                echo display_block( $file );
            }

            if( $toggle_sub_folders )
            {
                if( $sub_items )
                {
                    echo "<div class='sub' data-folder=\"$file\">";
                    build_blocks( $sub_items, $file );
                    echo "</div>";
                }
            }
        }

        // SORT BEFORE LOOP
        if( $sort_by == "date_asc" ) { ksort($objects['files']); }
        elseif( $sort_by == "date_desc" ) { krsort($objects['files']); }
        elseif( $sort_by == "name_asc" ) { natsort($objects['files']); }
        elseif( $sort_by == "name_desc" ) { arsort($objects['files']); }

        foreach($objects['files'] as $t => $file)
        {
            $fileExt = ext($file);
            if(in_array($file, $ignore_file_list)) { continue; }
            if(in_array($fileExt, $ignore_ext_list)) { continue; }
            echo display_block( $file );
        }
    }

    // GET THE BLOCKS STARTED, FALSE TO INDICATE MAIN FOLDER
    $items = scandir( dirname(__FILE__) );
    build_blocks( $items, false );
    ?>

    <?php if($toggle_sub_folders) { ?>
        <script type="text/javascript">
            $(document).ready(function()
            {
                $("a.dir").click(function(e)
                {
                    $(this).toggleClass('open');
                    $('.sub[data-folder="' + $(this).attr('href') + '"]').slideToggle();
                    e.preventDefault();
                });
            });
        </script>
    <?php } ?>
</div>

<?php
require '../views/footer.php';
?>