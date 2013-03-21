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
 * Strings for component 'apply', language 'ja', branch 'MOODLE_24_STABLE'
 *
 * @package   mod_apply
 * @copyright Fumi.Iseki http://www.nsl.tuis.ac.jp/
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

//
$string['modulenameplural'] = '申請フォーム';
$string['modulename'] = '申請フォーム';
$string['modulename_help'] = '各種の簡単な申請書を作成し，ユーザに提出させることができます．';
$string['description'] = '説明';


// Button
$string['save_entry_button']  = ' 申請書を送信 ';
$string['save_draft_button']  = ' 下書き保存 ';
$string['submit_form_button'] = ' 新規申請 ';
$string['next_page_button'] = ' 次のページ ';
$string['previous_page_button'] = ' 前のページ ';

$string['edit_entry_button'] = ' 編集 ';
$string['update_entry_button'] = ' 更新 ';
$string['cancel_entry_button'] = ' 取消 ';
$string['delete_entry_button'] = ' 削除 ';
$string['operation_entry_button'] = ' 操作 ';


// Menu
$string['apply:submit'] = '申請の提出';
//
$string['apply:addinstance'] = '新しい申請フォームを追加する';
$string['apply:applies'] = '申請を提出する';
$string['apply:createprivatetemplate'] = 'プライベートテンプレートを作成する';
$string['apply:createpublictemplate'] = 'パブリックテンプレートを作成する';
$string['apply:deletesubmissions'] = '完了した送信を削除する';
$string['apply:deletetemplate'] = 'テンプレートを削除する';
$string['apply:edititems'] = 'アイテムを編集する';
$string['apply:mapcourse'] = 'コースをグローバル申請フォームにマップする';
$string['apply:receivemail'] = 'メール通知を受信する';
$string['apply:view'] = '申請フォームを表示する';
$string['apply:viewanalysepage'] = '回答送信後，分析ページを表示する';
$string['apply:viewreports'] = 'レポートを表示する';


// Title
$string['title_title'] = 'タイトル';
$string['title_version'] = 'Ver.';
$string['title_class'] = '区分';
$string['title_ack']   = '受付';
$string['title_exec']  = '処理';
$string['title_check'] = 'チェック';


// tabs
$string['overview'] = '概要';
$string['show_entries'] = '申請書の表示';
$string['edit_items'] = '申請項目の編集';
$string['templates'] = 'テンプレート';


// mod_form
$string['name'] = '名称';
$string['time_open']   = '開始日時';
$string['time_open_help']  = 'あなたはユーザが書類提出のため申請フォームにアクセスできるようになる日時を指定することができます．チェックボックスがチェックされない場合，制限は定義されません．';
$string['time_close']  = '終了日時';
$string['time_close_help'] = 'あなたはユーザが書類提出のため申請フォームにアクセスできないようになる日時を指定することができます．チェックボックスがチェックされない場合，制限は定義されません．';
$string['apply_options'] = '申請フォームオプション';

$string['email_notification'] = '通知メールを送信する';
$string['email_notification_help'] = '有効にした場合，申請フォームの送信に関して管理者宛にメール通知されます';
$string['multiple_submit'] = '複数申請';
$string['multiple_submit_help'] = 'ユーザは無制限で申請フォームを送信することができます';

$string['use_calendar'] = 'カレンダーに登録';
$string['use_calendar_help'] = '申請書の提出期間をカレンダーに登録できます';

$string['username_manage'] = 'ユーザ名管理';
$string['username_manage_help'] = '表示される名前のパターンを選択できます';
$string['use_item'] = '{$a} を使用する';


// view
$string['entries_list_title'] = '提出済み申請書類一覧';


// submit
$string['entry_saved'] = 'あなたの申請書が送信されました';
$string['entry_saved_draft'] = 'あなたの申請書は下書きとして保存されました';
$string['saving_failed'] = '保存に失敗しました';
$string['saving_failed_because_missing_or_false_values'] = '値が入力されていないか，正しくないため，保存に失敗しました';

$string['edit_entry'] = '編集';
$string['update_entry'] = '更新';
$string['cancel_entry'] = '取消';
$string['delete_entry'] = '削除';
$string['operation_entry'] = '操作';




// show_entries
$string['user_pic']   	 = '画像';
$string['acked_notyet']  = '未処理';
$string['acked_accept']  = '受理';
$string['acked_reject']  = '却下';
$string['execed_done']	 = '処理済';
$string['execed_notyet'] = '未処理';
$string['class_draft']   = '下書き';
$string['class_newpost'] = '新規';
$string['class_update']  = '更新';
$string['class_cancel']  = '取消';
$string['no_title'] = 'タイトルなし';
$string['show_all'] = '{$a} 個のデータ全てを表示する';
$string['show_perpage'] = '1ページあたりの表示数を {$a} にする';
$string['not_submit_data'] = '指定されたデータは存在しません';


// edit_item
$string['save_item'] = '保存';
$string['add_item']  = '項目を追加する';
$string['items_are_required'] = 'アスタリスクが付けられた質問は必須回答です．';

$string['textarea'] = '長文回答';
$string['textarea_height'] = '行数';
$string['textarea_width'] = '幅';
$string['textfield'] = '短文回答';
$string['textfield_maxlength'] = '最大文字数';
$string['textfield_size'] = 'テキストフィールド幅';









$string['name_required'] = '名称を入力してください';
//

$string['add_items'] = '項目を追加する';



$string['add_pagebreak'] = '改ページを追加する';

$string['apply_is_not_open']  = '申請フォームはまだ利用できません';
$string['apply_is_not_ready'] = '申請フォームはまだ準備ができていません';
$string['apply_is_closed']    = '申請期間は既に終了しました';
$string['apply_is_disable']   = 'この申請を行う事はできません';
$string['apply_is_already_submitted'] = 'あなたは既に申請済みです';
$string['apply_is_enable']    = '申請を行う';


$string['submit_new_apply']   = '新規申請を行う';

$string['adjustment'] = '表示方向';
$string['analysis'] = '分析';
$string['anonymous'] = '匿名';
$string['anonymous_edit'] = 'ユーザ名を記録する';
$string['anonymous_entries'] = '匿名エントリ';
$string['anonymous_user'] = '匿名ユーザ';
$string['append_new_items'] = '新しいアイテムを追加する';
$string['autonumbering'] = '自動番号付け';
$string['autonumbering_help'] = 'それぞれの申請に対して自動ナンバリングを有効または無効にします．';
$string['average'] = '平均';
$string['bold'] = '太字';
$string['cancel_moving'] = '移動をキャンセルする';
$string['cannotmapapply'] = 'データベーストラブル，申請フォームをコースにマップできません．';
$string['cannotsavetempl'] = 'テンプレートの保存は，許可されていません．';
$string['cannotunmap'] = 'データベーストラブル，マップ解除できません．';
$string['captcha'] = 'Captcha';
$string['captchanotset'] = 'Captchaが設定されていません．';
$string['check'] = '多肢選択 - 複数回答';
$string['checkbox'] = '多肢選択 - 複数回答 (チェックボックス)';
$string['check_values'] = '考えられる回答';
$string['choosefile'] = 'ファイルを選択する';
$string['chosen_apply_response'] = '選択された申請フォームの回答';
$string['submitted'] = '送信';
$string['submitted_applys'] = '申請書類数';

//
$string['submit_the_form'] = '申請する ...';

$string['configallowfullanonymous'] = 'このオプションを有効にした場合，ログインせずに申請フォームを完了することができます．設定はホームページの申請フォームにのみ影響します．';
$string['confirmdeleteentry'] = '本当にこのエントリを削除してもよろしいですか?';


// delete_item.php
$string['confirm_delete_item'] = '本当にこの要素を削除してもよろしいですか?';




$string['confirmdeletetemplate'] = '本当にこのテンプレートを削除してもよろしいですか?';
$string['confirmusetemplate'] = '本当にこのテンプレートを使用しますか?';
$string['continue_the_form'] = 'フォームを続ける';
$string['count_of_nums'] = '桁数';
$string['courseid'] = 'コースID';
$string['creating_templates'] = 'これらの質問を新しいテンプレートとして保存する';
$string['delete_item'] = '質問を削除する';
$string['delete_old_items'] = '古いアイテムを削除する';
$string['delete_template'] = 'テンプレートを削除する';
$string['delete_templates'] = 'テンプレートを削除する ...';
$string['depending'] = '依存関係';
$string['depending_help'] = '依存アイテムを使用して他のアイテムの値に依存するアイテムを表示することができます．
<br />
<strong>以下，使用例です．</strong>
<br />
 <ul>
 <li>最初に他のアイテムが値を依存することになるアイテムを作成してください．</li>
<li>次に改ページ (Page break) を追加してください．</li>
<li>そして，最初に作成したアイテムの値に依存するアイテムを追加してください．アイテム作成フォーム内の「依存アイテム」リストから依存アイテム，そして「依存値」テキストボックスに必要な値を入力してください．</li>
</ul>
<strong>構造は次のようになります:</strong>
<ol>
<li>Item Q: あなたは自動車を所有していますか? A: yes/no</li>
<li>改ページ (Page break)</li>
<li>Item Q: あなたの自動車の色は何色ですか?
<br />
(このアイテムはアイテム1の値=yesに依存します)</li>
<li>Item Q: あなたはなぜ自動車を所有していないのですか?
<br />
 (このアイテムはアイテム1の値=noに依存します)</li>
 <li>
 ... 他のアイテム</li>
</ol>';
$string['dependitem'] = 'アイテムに依存する';
$string['dependvalue'] = '値に依存する';
$string['do_not_analyse_empty_submits'] = '空の送信を無視する';
$string['dropdown'] = '多肢選択 - 単一回答 (ドロップダウンリスト)';
$string['dropdownlist'] = '多肢選択 - 単一回答 (ドロップダウン)';
$string['dropdownrated'] = 'ドロップダウンリスト (評定)';
$string['dropdown_values'] = '回答';
$string['drop_apply'] = 'このコースから削除する';
$string['edit_item']  = '申請書を編集する';
$string['emailteachermail'] = '{$a->username} が申請フォーム「 {$a->apply} 」を完了しました．

下記ページにて内容を閲覧できます:

{$a->url}';
$string['emailteachermailhtml'] = '{$a->username} が申請フォーム「 {$a->apply} 」を完了しました．<br /><br />
<a href="{$a->url}">このページ</a>で詳細を閲覧できます．';
$string['export_questions'] = '質問をエクスポートする';
$string['export_to_excel'] = 'Excelにエクスポートする';
$string['apply_closes'] = '終了日時';

//

$string['apply_is_not_for_anonymous'] = '匿名ユーザは，申請フォームを利用できません．';
$string['apply_opens'] = '開始日時';
$string['file'] = 'ファイル';
$string['filter_by_course'] = 'コースでフィルタする';
$string['handling_error'] = '申請フォーム処理中にエラーが発生しました．';
$string['hide_no_select_option'] = '「未選択」オプションを隠す';
$string['horizontal'] = '水平';
$string['importfromthisfile'] = 'このファイルからインポートする';
$string['import_questions'] = '質問をインポートする';
$string['import_successfully'] = '正常にインポートされました．';
$string['info'] = '情報';
$string['infotype'] = '情報タイプ';
$string['insufficient_responses'] = '不十分な回答';
$string['insufficient_responses_for_this_group'] = 'このグループの回答数は，十分ではありません．';
$string['insufficient_responses_help'] = 'このグループの回答が不足しています．

申請フォームを匿名にするには，最低2つの回答が必要です．';
$string['item_label'] = 'ラベル';
$string['item_name'] = '申請書の項目数';
$string['label'] = 'ラベル';
$string['line_values'] = '評定';
$string['mapcourse'] = 'コースに申請フォームをマップする';
$string['mapcourse_help'] = 'デフォルトでは，あなたのMoodleメインページで作成した申請フォームフォームはサイト全体およびすべてのコースに申請フォームブロックを設置することで利用することができます．あなたは申請フォームをスティッキーブロックにすることで，強制的に表示することもできます．また，特定のコースにマッピングすることで，申請フォームフォームが表示されるコースを制限することもできます．';
$string['mapcourseinfo'] = 'この申請フォームは申請フォームブロックを使用してサイト全体で利用することができます．申請フォームをコースにマップすることにより，この申請フォームを利用できるコースを制限することができます．コースを検索して，この申請フォームをマップしてください．';
$string['mapcoursenone'] = 'マップされたコースはありません．この申請フォームは，すべてのコースで利用できます．';
$string['mapcourses'] = '申請フォームをコースにマップする';
$string['mapcourses_help'] = 'あなたの検索結果からコースを選択した後，コースにマップすることで，選択したコースとこの申請フォームを関連付けることができます．Ctrlキーを押しながら複数のコースを選択することも，Shiftキーを押しながら一連のコースを選択することもできます．コースに関連付けた申請フォームはいつでも関連付けを解除することができます．';
$string['mappedcourses'] = 'マップ済みコース';
$string['max_args_exceeded'] = '最大6つの引数を処理することができます．引数が多すぎます:';
$string['maximal'] = '最大';
$string['messageprovider:message'] = '申請フォームリマインダ';
$string['messageprovider:submission'] = '申請フォーム通知';
$string['mode'] = 'モード';
$string['movedown_item'] = 'この質問を下げる';
$string['move_here'] = 'ここに移動する';
$string['move_item'] = 'この質問を移動する';
$string['moveup_item'] = 'この質問を上げる';
$string['multichoice'] = '多肢選択';
$string['multichoicerated'] = '多肢選択 (評定)';
$string['multichoicetype'] = '多肢選択タイプ';
$string['multichoice_values'] = '多肢選択値';


$string['no_handler'] = 'アクションハンドラがありません:';
$string['no_itemlabel'] = 'ラベルなし';
$string['no_itemname'] = '無題';
$string['no_items_available_yet'] = '質問はまだ設定されていません．';
$string['non_anonymous'] = 'ユーザ名を記録し，回答とともに表示する';
$string['non_anonymous_entries'] = '非匿名エントリ';
$string['non_respondents_students'] = '未回答の学生';
$string['notavailable'] = 'この申請フォームは，利用できません．';
$string['not_completed_yet'] = 'まだ完了していません．';
$string['no_templates_available_yet'] = 'テンプレートはまだ利用できません．';
$string['not_selected'] = '未選択';
$string['not_started'] = '未開始';
$string['numeric'] = '数値回答';
$string['numeric_range_from'] = '開始数値';
$string['numeric_range_to'] = '終了数値';
$string['of'] = '/';
$string['oldvaluespreserved'] = 'すべての古い問題および割り当てられた値は保持されます';
$string['oldvalueswillbedeleted'] = '現在の問題およびすべてのユーザ回答が削除されます';
$string['only_one_captcha_allowed'] = '1申請フォームあたり，1つのCAPTCHAのみ許可されています．';
$string['page'] = 'ページ';
$string['page_after_submit'] = '回答送信後のページ';
$string['pagebreak'] = 'ページブレーク';
$string['page-mod-apply-x'] = 'すべての申請フォームモジュールページ';
$string['parameters_missing'] = 'パラメータが入力されていません:';
$string['picture'] = '画像';
$string['picture_file_list'] = '画像リスト';
$string['picture_values'] = '一覧より１つまたはそれ以上の<br />画像を選択してください:';
$string['pluginadministration'] = '申請フォーム管理';
$string['pluginname'] = '申請フォーム';
$string['position'] = 'ポジション';
$string['preview'] = 'プレビュー';
$string['preview_help'] = 'このプレビューにて，あなたは質問の順番を変更することができます．';
$string['public'] = '公開';
$string['question'] = '質問question';
$string['questions'] = '質問questions';
$string['radio'] = '多肢選択 - 単一回答';
$string['radiobutton'] = '多肢選択 - 単一回答 (ラジオボタン)';
$string['radiobutton_rated'] = 'ラジオボタン (評定)';
$string['radiorated'] = 'ラジオボタン (評定)';
$string['radio_values'] = '回答';
$string['ready_applys'] = '準備済み申請フォーム';
$string['relateditemsdeleted'] = 'この問題に関する，すべてのユーザの回答も削除されます．';
$string['required'] = '必須';
$string['resetting_data'] = '申請フォームをリセットする';
$string['resetting_applys'] = '申請フォームのリセット';
$string['response_nr'] = '回答No';
$string['responses'] = '回答';
$string['responsetime'] = '回答時間';
$string['save_as_new_item'] = '新しい質問として保存する';
$string['save_as_new_template'] = '新しいテンプレートとして保存する';
$string['save_item'] = '質問を保存する';
$string['search_course'] = 'コースを検索する';
$string['searchcourses'] = 'コースを検索する';
$string['searchcourses_help'] = 'あなたがこの申請フォームに関連付けたいコースのコードまたは名称を使用して検索してください．';
$string['selected_dump'] = '選択された$SESSION変数のインデックスは，以下にダンプされます:';
$string['send'] = '送信';
$string['send_message'] = 'メッセージを送信する';
$string['separator_decimal'] = '.';
$string['separator_thousand'] = ',';
$string['show_analysepage_after_submit'] = '回答送信後，分析ページを表示する';
$string['show_entry'] = '申請を表示する';
$string['show_nonrespondents'] = '未回答者を表示する';
$string['site_after_submit'] = '回答送信後のサイト';
$string['sort_by_course'] = 'コース名で並び替える';
$string['start'] = '開始';
$string['started'] = '開始済み';
$string['stop'] = '終了';
$string['subject'] = '件名';
$string['switch_group'] = 'グループを切り替える';
$string['switch_item_to_not_required'] = '必須回答を解除する';
$string['switch_item_to_required'] = '必須回答にする';
$string['template_saved'] = 'テンプレートが保存されました．';

$string['there_are_no_settings_for_recaptcha'] = 'CAPTCHAが設定されていません．';
$string['typemissing'] = '「type」の値がありません．';
$string['update_item'] = '質問の変更を保存する';
$string['url_for_continue'] = '「続ける」ボタンのURI';
$string['url_for_continue_button'] = '「続ける」ボタンのURI';
$string['url_for_continue_help'] = 'デフォルトでは「続ける」ボタンを使用して申請フォームを送信した後，コースページに戻ります．あなたは「続ける」ボタンをクリックした後に移動する別のページのURIを指定することができます．';
$string['use_one_line_for_each_value'] = '<br />1行に1つの回答を入力してください!';
$string['use_this_template'] = 'このテンプレートを使用する';
$string['using_templates'] = 'テンプレートの使用';
$string['vertical'] = '垂直';
$string['viewcompleted'] = '完了済み申請フォーム';
$string['viewcompleted_help'] = 'あなたはコースまたは質問により検索可能な完了済み申請フォームフォームを閲覧することができます．申請フォームの回答はExcelにエクスポートすることができます．';
