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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Brazilian Portuguese strings for VideoTrack.
 *
 * @package     mod_videotrack
 * @copyright   2026 Yeison Díaz
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['accentcolor'] = 'Cor de destaque';
$string['accentcolor_help'] = 'Cor hexadecimal opcional (por exemplo, #0f6cbf). Deixe em branco para herdar a cor do tema do Moodle.';
$string['backtocourse'] = 'Voltar ao curso';
$string['captions'] = 'Legendas (WebVTT)';
$string['captions_help'] = 'Arquivo de legenda .vtt opcional para vídeos locais ou links diretos.';
$string['completed'] = 'Concluído';
$string['completiondetail:targetpercent'] = 'Assistir a pelo menos {$a}% do vídeo';
$string['completionpercenterror'] = 'O percentual obrigatório deve estar entre 1 e 100.';
$string['completionwatchpercent'] = 'O estudante deve assistir a pelo menos';
$string['completionwatchpercent_help'] = 'Quando habilitado, o Moodle marca a atividade como concluída somente depois que o estudante atingir o percentual de visualização definido.';
$string['displaymode'] = 'Exibição na página do curso';
$string['displaymode:inline'] = 'Player incorporado diretamente na página do curso';
$string['displaymode:page'] = 'Página padrão da atividade';
$string['displaymode:popup'] = 'Card na página do curso (abre o player em popup)';
$string['displaymode_help'] = 'Escolha como o VideoTrack será exibido: na página padrão, em um card com popup flutuante, ou com o player embutido diretamente no tópico do curso.';
$string['displaysettings'] = 'Exibição e reprodução';
$string['error_accentcolor'] = 'Informe uma cor hexadecimal válida, como #0f6cbf, ou deixe o campo em branco.';
$string['error_nouploadorurl'] = 'Você deve informar uma URL de vídeo ou enviar um arquivo de vídeo.';
$string['error_novideosupport'] = 'Seu navegador não suporta vídeos em HTML5.';
$string['error_progresslock'] = 'O progresso já está sendo atualizado. Aguarde um instante e tente novamente.';
$string['error_sourceplayback'] = 'Não foi possível reproduzir o vídeo. Verifique a URL, as permissões de acesso e o formato.';
$string['error_unsupportedurl'] = 'Use uma URL do YouTube ou de compartilhamento do HeyGen, um link direto de vídeo (MP4, WebM, OGG, M4V ou MOV), ou envie um arquivo.';
$string['eventcoursemoduleviewed'] = 'Atividade VideoTrack visualizada';
$string['focusmode'] = 'Ativar modo foco';
$string['focusmode_help'] = 'Quando ativado, o popup usa uma visualização em tela cheia com menos distrações e o vídeo é pausado se o estudante sair da aba ou janela ativa.';
$string['focusmode_paused_msg'] = 'Vídeo pausado porque você mudou de aba ou minimizou a janela. Retorne a esta aba para continuar assistindo.';
$string['focusmode_paused_title'] = 'Modo Foco Ativo';
$string['focusmodebadge'] = 'Modo foco';
$string['highestpercent'] = 'Maior percentual assistido';
$string['lastaccess'] = 'Último acesso';
$string['launchvideo'] = 'Assistir ao vídeo';
$string['modulename'] = 'VideoTrack';
$string['modulename_help'] = 'A atividade VideoTrack permite incorporar um vídeo e exigir que o estudante assista a um percentual específico.';
$string['modulenameplural'] = 'VideoTracks';
$string['noresponses'] = 'Ainda não há progresso registrado para este vídeo.';
$string['notapplicable'] = 'Não se aplica';
$string['openvideo'] = 'Abrir vídeo';
$string['pluginadministration'] = 'Administração do VideoTrack';
$string['pluginname'] = 'VideoTrack';
$string['preventforward'] = 'Bloquear avanço do vídeo até a conclusão';
$string['preventforward_help'] = 'Quando ativado, o estudante não pode adiantar a barra do vídeo além do ponto já assistido até atingir a porcentagem de conclusão exigida. Após concluir, a navegação é liberada automaticamente.';
$string['privacy:metadata:videotrack_progress'] = 'Armazena o progresso de reprodução do vídeo e o status de conclusão do usuário.';
$string['privacy:metadata:videotrack_progress:duration'] = 'A duração informada pelo reprodutor de vídeo.';
$string['privacy:metadata:videotrack_progress:highestpercent'] = 'O maior percentual do vídeo assistido pelo usuário.';
$string['privacy:metadata:videotrack_progress:highesttime'] = 'O tempo máximo de reprodução em segundos alcançado pelo usuário.';
$string['privacy:metadata:videotrack_progress:iscompleted'] = 'Se o usuário atingiu o percentual obrigatório de conclusão.';
$string['privacy:metadata:videotrack_progress:lastheartbeat'] = 'O horário do último sinal de reprodução.';
$string['privacy:metadata:videotrack_progress:lastposition'] = 'A última posição de reprodução usada para retomar o vídeo.';
$string['privacy:metadata:videotrack_progress:timecreated'] = 'Data e hora em que o registro de progresso foi criado.';
$string['privacy:metadata:videotrack_progress:timemodified'] = 'Data e hora da última alteração do registro de progresso.';
$string['privacy:metadata:videotrack_progress:userid'] = 'ID do usuário.';
$string['privacy:metadata:videotrack_progress:watchedsegments'] = 'Os intervalos não sobrepostos do vídeo que o usuário assistiu.';
$string['progressfree'] = 'Este vídeo está em modo livre. Você pode assistir e avançar livremente.';
$string['progresshint'] = 'Você deve assistir a pelo menos <strong>{$a}%</strong> do vídeo para concluir esta atividade.';
$string['progresstitle'] = 'Progresso de visualização';
$string['progressupdated'] = 'Última atualização do progresso';

$string['report'] = 'Relatório de progresso';
$string['resumebutton'] = 'Continuar de {$a}';
$string['resumeplayback'] = 'Retomar vídeo';
$string['seek_locked_msg'] = 'O avanço do vídeo está travado até você atingir o percentual de conclusão.';
$string['seek_unlocked_msg'] = 'Parabéns! Atividade concluída e navegação livre no vídeo liberada.';
$string['student'] = 'Estudante';
$string['successmsg'] = 'Parabéns! Você atingiu o percentual obrigatório e pode continuar.';
$string['targetmarker'] = 'Meta necessária: {$a}%';
$string['targetpercent'] = 'Percentual obrigatório (%)';
$string['targetpercent_help'] = 'Percentual do vídeo que o estudante deve assistir para concluir a atividade.';
$string['videofile'] = 'Arquivo de vídeo (local)';
$string['videofile_help'] = 'Envie o arquivo de vídeo. Se uma URL externa for informada, ela terá prioridade.';
$string['videosettings'] = 'Fonte do vídeo';
$string['videotrack:addinstance'] = 'Adicionar uma nova atividade VideoTrack';
$string['videotrack:view'] = 'Visualizar VideoTrack';
$string['videotrack:viewreport'] = 'Visualizar relatório de progresso';
$string['videourl'] = 'URL do vídeo (externa)';
$string['videourl_help'] = 'Cole um link do YouTube ou uma URL direta de vídeo. Para enviar um arquivo ao Moodle, deixe este campo vazio e use o seletor de arquivo abaixo.';
