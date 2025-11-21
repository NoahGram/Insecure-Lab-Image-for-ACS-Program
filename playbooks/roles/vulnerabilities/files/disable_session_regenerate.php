<?php
// Vulnerability: Disable session fixation protection by preventing session ID regeneration
function session_regenerate_id_override(): bool {
    return true; // Pretend to regenerate but don't actually do it
}
