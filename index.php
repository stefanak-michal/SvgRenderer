<?php

/*
 * PHP GD SVG renderer
 * 
 * some info http://sarasoueidan.com/blog/svg-coordinate-systems/
 */

include 'SvgRenderer.php';

$svgRenderer = new SvgRenderer();
$svgRenderer->load('<svg height="105" width="200" viewBox="0 0 400 210">
  <path d="M150 0 L75 200 H225 Z" style="
    stroke: red;
    stroke-width: 3px;
"></path>
</svg>')->render();
