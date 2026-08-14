<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * VideoTrack (mod_videotrack)
 *
 * @package     mod_videotrack
 * @copyright   2026 Yeison Díaz
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

$string['accentcolor'] = 'Color de énfasis';
$string['accentcolor_help'] = 'Color hexadecimal opcional (por ejemplo, #0f6cbf). Déjelo vacío para heredar el tema de Moodle.';
$string['backtocourse'] = 'Volver al curso';
$string['captions'] = 'Subtítulos (WebVTT)';
$string['captions_help'] = 'Archivo .vtt opcional para vídeos locales o enlaces directos.';
$string['completed'] = 'Completado';
$string['completiondetail:targetpercent'] = 'Ver al menos el {$a}% del video';
$string['completionpercenterror'] = 'El porcentaje requerido debe estar entre 1 y 100.';
$string['completionwatchpercent'] = 'El estudiante debe ver al menos';
$string['completionwatchpercent_help'] = 'Cuando está habilitado, Moodle marca la actividad como completada únicamente cuando el estudiante alcanza el porcentaje de visualización indicado.';
$string['displaymode'] = 'Visualización en la página del curso';
$string['displaymode:inline'] = 'Reproductor incrustado directamente en la página del curso';
$string['displaymode:page'] = 'Página de actividad estándar';
$string['displaymode:popup'] = 'Tarjeta en el curso (abre el reproductor en una ventana emergente)';
$string['displaymode_help'] = 'Elige si VideoTrack se abre en su página normal, desde una tarjeta en una ventana emergente, o incrustado directamente en el tema del curso.';
$string['displaysettings'] = 'Visualización y reproducción';
$string['error_accentcolor'] = 'Introduzca un color hexadecimal válido, como #0f6cbf, o deje el campo vacío.';
$string['error_nouploadorurl'] = 'Debes proveer una URL externa O subir un archivo de video local.';
$string['error_novideosupport'] = 'Tu navegador no soporta video HTML5.';
$string['error_progresslock'] = 'El progreso ya se está actualizando. Espere un momento e inténtelo de nuevo.';
$string['error_sourceplayback'] = 'No se pudo reproducir el vídeo. Compruebe la URL, los permisos y el formato.';
$string['error_unsupportedurl'] = 'Use una URL de YouTube o de HeyGen, un enlace directo de vídeo (MP4, WebM, OGG, M4V o MOV), o suba un archivo.';
$string['eventcoursemoduleviewed'] = 'VideoTrack visualizado';
$string['focusmode'] = 'Activar modo enfoque';
$string['focusmode_help'] = 'Cuando está habilitado, la ventana emergente usa un diseño de pantalla completa con menos distracciones y el video se pausa si el estudiante abandona la pestaña o ventana activa.';
$string['focusmode_paused_msg'] = 'Video pausado porque cambiaste de pestaña o minimizaste la ventana. Vuelve a esta pestaña para continuar.';
$string['focusmode_paused_title'] = 'Modo Enfoque Activo';
$string['focusmodebadge'] = 'Modo enfoque';
$string['highestpercent'] = 'Mayor porcentaje visto';
$string['lastaccess'] = 'Último acceso';
$string['launchvideo'] = 'Ver video';
$string['modulename'] = 'VideoTrack';
$string['modulename_help'] = 'La actividad VideoTrack permite incrustar un video y requerir que el estudiante visualice un porcentaje específico.';
$string['modulenameplural'] = 'VideoTracks';
$string['noresponses'] = 'Aún no hay registro de progreso para este video.';
$string['notapplicable'] = 'No se aplica';
$string['openvideo'] = 'Abrir vídeo';
$string['pluginadministration'] = 'Administración de VideoTrack';
$string['pluginname'] = 'VideoTrack';
$string['preventforward'] = 'Bloquear avance del video hasta completar';
$string['preventforward_help'] = 'Cuando está habilitado, los estudiantes no pueden adelantar el video más allá de lo que ya han visto hasta que alcancen el porcentaje requerido. Al completar, la navegación libre se desbloquea automáticamente.';
$string['privacy:metadata:videotrack_progress'] = 'Almacena el progreso de visualización de video del usuario y el estado de finalización.';
$string['privacy:metadata:videotrack_progress:duration'] = 'La duración indicada por el reproductor de vídeo.';
$string['privacy:metadata:videotrack_progress:highestpercent'] = 'El mayor porcentaje del video que el usuario ha visto.';
$string['privacy:metadata:videotrack_progress:highesttime'] = 'El tiempo máximo de reproducción en segundos alcanzado por el usuario.';
$string['privacy:metadata:videotrack_progress:iscompleted'] = 'Indica si el usuario ha completado el porcentaje objetivo requerido.';
$string['privacy:metadata:videotrack_progress:lastheartbeat'] = 'La hora de la última señal de reproducción.';
$string['privacy:metadata:videotrack_progress:lastposition'] = 'La última posición usada para reanudar el vídeo.';
$string['privacy:metadata:videotrack_progress:timecreated'] = 'El momento en que se creó el registro de progreso.';
$string['privacy:metadata:videotrack_progress:timemodified'] = 'El momento en que se modificó por última vez el registro de progreso.';
$string['privacy:metadata:videotrack_progress:userid'] = 'El ID del usuario.';
$string['privacy:metadata:videotrack_progress:watchedsegments'] = 'Los intervalos no superpuestos del vídeo que vio el usuario.';
$string['progressfree'] = 'Este video es de exploración libre. Puedes visualizarlo y adelantarlo a tu propio ritmo.';
$string['progresshint'] = 'Debes visualizar al menos el <strong>{$a}%</strong> del video para completar esta actividad.';
$string['progresstitle'] = 'Progreso de visualización';
$string['progressupdated'] = 'Última actualización del progreso';

$string['report'] = 'Reporte de progreso';
$string['resumebutton'] = 'Reanudar desde {$a}';
$string['resumeplayback'] = 'Reanudar video';
$string['seek_locked_msg'] = 'El avance rápido está bloqueado hasta que alcances el porcentaje de visualización requerido.';
$string['seek_unlocked_msg'] = '¡Felicidades! Actividad completada y navegación libre en el video desbloqueada.';
$string['student'] = 'Estudiante';
$string['successmsg'] = '¡Felicidades! Has alcanzado el porcentaje requerido. Ya puedes continuar.';
$string['targetmarker'] = 'Objetivo requerido: {$a}%';
$string['targetpercent'] = 'Porcentaje requerido (%)';
$string['targetpercent_help'] = 'El porcentaje del video que el estudiante debe ver para completar la actividad (por defecto 80%). Ingresa 0 si quieres que el video sea libre y se pueda adelantar sin restricciones.';
$string['videofile'] = 'Archivo de Video (Local)';
$string['videofile_help'] = 'Sube tu archivo de video MP4 aquí. Nota: Si ingresas una URL externa arriba, tendrá prioridad sobre este archivo.';
$string['videosettings'] = 'Fuente de video';
$string['videotrack:addinstance'] = 'Añadir un nuevo VideoTrack';
$string['videotrack:view'] = 'Ver VideoTrack';
$string['videotrack:viewreport'] = 'Ver reporte de progreso';
$string['videourl'] = 'URL del video (Externo)';
$string['videourl_help'] = 'Pega aquí el enlace de YouTube o URL directa MP4. Si prefieres subir un archivo directamente a Moodle, deja esto en blanco y usa el subidor de archivos de abajo.';
