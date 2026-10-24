<?php
namespace App\Util;

use App\Models\Area;
use App\Models\ASkill;
use App\Models\Enemy;
use App\Models\Evo;
use App\Models\LinkSystem;
use App\Models\LSkill;
use App\Models\NSkill;
use App\Models\PSkill;
use App\Models\Quest;
use App\Models\QuestFloor;
use App\Models\AreaCategory;
use App\Models\Unit;
use App\Models\UserRank;
use App\Models\Translate;
use App\Util\FunctionUtil;
use App\Util\ImageUtil;
use \Cache;
use \DB;

/* 
 * Since the database is freezed, cache time of all function set to forever
 * 
 * Previous setting is one week
 * $expiresAt = new Carbon('next friday');
 * Cache::put($key, $data, $expiresAt);
 */
class CacheUtil{
    private $function;
    private $imageUtil;

    public function __construct(){
        $this->function = new FunctionUtil();
        $this->imageUtil = new ImageUtil();
    }

    public function area($id){
        $key = 'blade_area'.$id;
        if (Cache::has($key)){
            $data = Cache::get($key);
        } else {
            $area = Area::where('fix_id','=',$id)->firstOrFail();
            $data = compact('area');
            $data['area_cate_name'] = $area->areaCate()->area_cate_name;
            $data['quests'] = Quest::where('area_id','=',$id)->get();
            // cache
            Cache::forever($key, $data);
        }
        return $data;
    }
    
    public function quest($id){
        $key = 'blade_quest'.$id;
        if (Cache::has($key)){
            $data = Cache::get($key);
        } else {
            $quest = Quest::where('fix_id','=',$id)->firstOrFail();
            $data = compact('quest');

            $area = $quest->area();
            $data['area_cate_name'] = $area->areaCate()->area_cate_name;
            $data['area_name'] = $area->area_name;
            $data['area_id'] = $area->fix_id;
            $areaKey = $area->areaKey();
            if(!is_null($areaKey))
                $data['area_key'] = $areaKey->key_name;

            $storycn = $quest->getTranslate();
            if($storycn !== null)
                $data['storycn'] = $storycn->text;

            // quest requirement
            if($quest->quest_requirement_id != 0){
                $quest_requirement = $quest->questRequirement();
                if($quest->battle_chain == 2)
                    $data['quest_requirement'][] = '連鎖戰鬥: 有';
                if($quest->enable_continue == 1)
                    $data['quest_requirement'][] = '不可使用硬幣復活';
                // elem
                if($quest_requirement->elem_fire == 2 || $quest_requirement->elem_water == 2 || $quest_requirement->elem_wind == 2 || $quest_requirement->elem_light == 2 || $quest_requirement->elem_dark == 2 || $quest_requirement->elem_naught == 2){
                    $first = true;
                    $quest_requirement_elem = '禁止使用';
                    if($quest_requirement->elem_fire == 2){
                        if($first){$first = false;}else{$quest_requirement_elem .= '、';}
                        $quest_requirement_elem .= '火';
                    }
                    if($quest_requirement->elem_water == 2){
                        if($first){$first = false;}else{$quest_requirement_elem .= '、';}
                        $quest_requirement_elem .= '水';
                    }
                    if($quest_requirement->elem_wind == 2){
                        if($first){$first = false;}else{$quest_requirement_elem .= '、';}
                        $quest_requirement_elem .= '風';
                    }
                    if($quest_requirement->elem_light == 2){
                        if($first){$first = false;}else{$quest_requirement_elem .= '、';}
                        $quest_requirement_elem .= '光';
                    }
                    if($quest_requirement->elem_dark == 2){
                        if($first){$first = false;}else{$quest_requirement_elem .= '、';}
                        $quest_requirement_elem .= '暗';
                    }
                    if($quest_requirement->elem_naught == 2){
                        if($first){$first = false;}else{$quest_requirement_elem .= '、';}
                        $quest_requirement_elem .= '無';
                    }
                    $quest_requirement_elem .= '屬性以外的Unit';
                    if($quest_requirement->num_elem == 1)
                        $quest_requirement_elem .= '(不能有副屬性)';
                    $data['quest_requirement'][] = $quest_requirement_elem;
                }
                // kind
                if($quest_requirement->kind_human == 2 || $quest_requirement->kind_fairy == 2 || $quest_requirement->kind_demon == 2 || $quest_requirement->kind_dragon == 2 || $quest_requirement->kind_machine == 2 || $quest_requirement->kind_beast == 2 || $quest_requirement->kind_god == 2 || $quest_requirement->kind_egg == 2){
                    $first = true;
                    $quest_requirement_kind = '禁止使用';
                    if($quest_requirement->kind_human == 2){
                        if($first){$first = false;}else{$quest_requirement_kind .= '、';}
                        $quest_requirement_kind .= '人類';
                    }
                    if($quest_requirement->kind_fairy == 2){
                        if($first){$first = false;}else{$quest_requirement_kind .= '、';}
                        $quest_requirement_kind .= '妖精類';
                    }
                    if($quest_requirement->kind_demon == 2){
                        if($first){$first = false;}else{$quest_requirement_kind .= '、';}
                        $quest_requirement_kind .= '魔物類';
                    }
                    if($quest_requirement->kind_dragon == 2){
                        if($first){$first = false;}else{$quest_requirement_kind .= '、';}
                        $quest_requirement_kind .= '龍類';
                    }
                    if($quest_requirement->kind_machine == 2){
                        if($first){$first = false;}else{$quest_requirement_kind .= '、';}
                        $quest_requirement_kind .= '機械類';
                    }
                    if($quest_requirement->kind_beast == 2){
                        if($first){$first = false;}else{$quest_requirement_kind .= '、';}
                        $quest_requirement_kind .= '獸類';
                    }
                    if($quest_requirement->kind_god == 2){
                        if($first){$first = false;}else{$quest_requirement_kind .= '、';}
                        $quest_requirement_kind .= '神類';
                    }
                    if($quest_requirement->kind_egg == 2){
                        if($first){$first = false;}else{$quest_requirement_kind .= '、';}
                        $quest_requirement_kind .= '強化合成類';
                    }
                    $quest_requirement_kind .= '以外的Unit';
                    if($quest_requirement->num_kind == 1)
                        $quest_requirement_kind .= '(不能有副種族)';
                    $data['quest_requirement'][] = $quest_requirement_kind;
                }
                if($quest_requirement->num_unit != 0)
                    $data['quest_requirement'][] = '只能攜帶'.$quest_requirement->num_unit.'隻Unit闖關';
                if($quest_requirement->much_name == 2)
                    $data['quest_requirement'][] = '禁止使用同名Unit';
                if($quest_requirement->require_unit_00 != 0){
                    for($i = 0; $i < 5; $i++){
                        $data['quest_requirement']['require_unit'][] = $quest_requirement->require_unit($i);
                    }
                }
                if($quest_requirement->limit_cost != 0)
                    $data['quest_requirement'][] = '禁止使用'.($quest_requirement->limit_cost+1).' COST或以上的Unit';
                if($quest_requirement->limit_cost_total != 0)
                    $data['quest_requirement'][] = '隊伍COST禁止超越'.$quest_requirement->limit_cost_total;
                if($quest_requirement->limit_unit_lv != 0)
                    $data['quest_requirement'][] = '禁止使用Lv.'.$quest_requirement->limit_unit_lv.'或以上的Unit';
                if($quest_requirement->limit_unit_lv_total != 0)
                    $data['quest_requirement'][] = '隊伍Lv.禁止超越'.$quest_requirement->limit_unit_lv_total;
                if($quest_requirement->limit_rank != 0)
                    $data['quest_requirement'][] = 'RANK'.($quest_requirement->limit_rank-1).'以下禁止入場';
                //rule_disable_as, rule_disable_ls, rule_heal_half, rule_disable_affinity
                if($quest_requirement->fix_unit_00_enable == 2){
                    $data['quest_requirement']['fix_team']['fix_unit'][0]['unit'] = $quest_requirement->fix_unit(0);
                    $data['quest_requirement']['fix_team']['fix_unit'][0]['lv'] = $quest_requirement->fix_unit_00_lv;
                    $data['quest_requirement']['fix_team']['fix_unit'][0]['lbs_lv'] = $quest_requirement->fix_unit_00_lbs_lv; // limit break skill lv
                    $data['quest_requirement']['fix_team']['fix_unit'][0]['plus_hp'] = $quest_requirement->fix_unit_00_plus_hp;
                    $data['quest_requirement']['fix_team']['fix_unit'][0]['plus_atk'] = $quest_requirement->fix_unit_00_plus_atk;
                    if($quest_requirement->fix_unit_00_link_id != 0){
                        $data['quest_requirement']['fix_team']['link_unit'][0]['unit'] = $quest_requirement->link_unit(0);
                        $data['quest_requirement']['fix_team']['link_unit'][0]['lv'] = $quest_requirement->fix_unit_00_link_lv;
                        $data['quest_requirement']['fix_team']['link_unit'][0]['plus_hp'] = $quest_requirement->fix_unit_00_link_plus_hp;
                        $data['quest_requirement']['fix_team']['link_unit'][0]['plus_atk'] = $quest_requirement->fix_unit_00_link_plus_atk;
                        $data['quest_requirement']['fix_team']['link_unit'][0]['link_point'] = $quest_requirement->fix_unit_00_link_lbs_lv;
                    }
                }
                if($quest_requirement->fix_unit_01_enable == 2){
                    $data['quest_requirement']['fix_team']['fix_unit'][1]['unit'] = $quest_requirement->fix_unit(1);
                    $data['quest_requirement']['fix_team']['fix_unit'][1]['lv'] = $quest_requirement->fix_unit_01_lv;
                    $data['quest_requirement']['fix_team']['fix_unit'][1]['lbs_lv'] = $quest_requirement->fix_unit_01_lbs_lv; // limit break skill lv
                    $data['quest_requirement']['fix_team']['fix_unit'][1]['plus_hp'] = $quest_requirement->fix_unit_01_plus_hp;
                    $data['quest_requirement']['fix_team']['fix_unit'][1]['plus_atk'] = $quest_requirement->fix_unit_01_plus_atk;
                    if($quest_requirement->fix_unit_01_link_id != 0){
                        $data['quest_requirement']['fix_team']['link_unit'][1]['unit'] = $quest_requirement->link_unit(1);
                        $data['quest_requirement']['fix_team']['link_unit'][1]['lv'] = $quest_requirement->fix_unit_01_link_lv;
                        $data['quest_requirement']['fix_team']['link_unit'][1]['plus_hp'] = $quest_requirement->fix_unit_01_link_plus_hp;
                        $data['quest_requirement']['fix_team']['link_unit'][1]['plus_atk'] = $quest_requirement->fix_unit_01_link_plus_atk;
                        $data['quest_requirement']['fix_team']['link_unit'][1]['link_point'] = $quest_requirement->fix_unit_01_link_lbs_lv;
                    }
                }
                if($quest_requirement->fix_unit_02_enable == 2){
                    $data['quest_requirement']['fix_team']['fix_unit'][2]['unit'] = $quest_requirement->fix_unit(2);
                    $data['quest_requirement']['fix_team']['fix_unit'][2]['lv'] = $quest_requirement->fix_unit_02_lv;
                    $data['quest_requirement']['fix_team']['fix_unit'][2]['lbs_lv'] = $quest_requirement->fix_unit_02_lbs_lv; // limit break skill lv
                    $data['quest_requirement']['fix_team']['fix_unit'][2]['plus_hp'] = $quest_requirement->fix_unit_02_plus_hp;
                    $data['quest_requirement']['fix_team']['fix_unit'][2]['plus_atk'] = $quest_requirement->fix_unit_02_plus_atk;
                    if($quest_requirement->fix_unit_02_link_id != 0){
                        $data['quest_requirement']['fix_team']['link_unit'][2]['unit'] = $quest_requirement->link_unit(2);
                        $data['quest_requirement']['fix_team']['link_unit'][2]['lv'] = $quest_requirement->fix_unit_02_link_lv;
                        $data['quest_requirement']['fix_team']['link_unit'][2]['plus_hp'] = $quest_requirement->fix_unit_02_link_plus_hp;
                        $data['quest_requirement']['fix_team']['link_unit'][2]['plus_atk'] = $quest_requirement->fix_unit_02_link_plus_atk;
                        $data['quest_requirement']['fix_team']['link_unit'][2]['link_point'] = $quest_requirement->fix_unit_02_link_lbs_lv;
                    }
                }
                if($quest_requirement->fix_unit_03_enable == 2){
                    $data['quest_requirement']['fix_team']['fix_unit'][3]['unit'] = $quest_requirement->fix_unit(0);
                    $data['quest_requirement']['fix_team']['fix_unit'][3]['lv'] = $quest_requirement->fix_unit_03_lv;
                    $data['quest_requirement']['fix_team']['fix_unit'][3]['lbs_lv'] = $quest_requirement->fix_unit_03_lbs_lv; // limit break skill lv
                    $data['quest_requirement']['fix_team']['fix_unit'][3]['plus_hp'] = $quest_requirement->fix_unit_03_plus_hp;
                    $data['quest_requirement']['fix_team']['fix_unit'][3]['plus_atk'] = $quest_requirement->fix_unit_03_plus_atk;
                    if($quest_requirement->fix_unit_03_link_id != 0){
                        $data['quest_requirement']['fix_team']['link_unit'][3]['unit'] = $quest_requirement->link_unit(3);
                        $data['quest_requirement']['fix_team']['link_unit'][3]['lv'] = $quest_requirement->fix_unit_03_link_lv;
                        $data['quest_requirement']['fix_team']['link_unit'][3]['plus_hp'] = $quest_requirement->fix_unit_03_link_plus_hp;
                        $data['quest_requirement']['fix_team']['link_unit'][3]['plus_atk'] = $quest_requirement->fix_unit_03_link_plus_atk;
                        $data['quest_requirement']['fix_team']['link_unit'][3]['link_point'] = $quest_requirement->fix_unit_03_link_lbs_lv;
                    }
                }
                if($quest_requirement->fix_unit_04_enable == 2){
                    $data['quest_requirement']['fix_team']['fix_unit'][4]['unit'] = $quest_requirement->fix_unit(4);
                    $data['quest_requirement']['fix_team']['fix_unit'][4]['lv'] = $quest_requirement->fix_unit_04_lv;
                    $data['quest_requirement']['fix_team']['fix_unit'][4]['lbs_lv'] = $quest_requirement->fix_unit_04_lbs_lv; // limit break skill lv
                    $data['quest_requirement']['fix_team']['fix_unit'][4]['plus_hp'] = $quest_requirement->fix_unit_04_plus_hp;
                    $data['quest_requirement']['fix_team']['fix_unit'][4]['plus_atk'] = $quest_requirement->fix_unit_04_plus_atk;
                    if($quest_requirement->fix_unit_04_link_id != 0){
                        $data['quest_requirement']['fix_team']['link_unit'][4]['unit'] = $quest_requirement->link_unit(4);
                        $data['quest_requirement']['fix_team']['link_unit'][4]['lv'] = $quest_requirement->fix_unit_04_link_lv;
                        $data['quest_requirement']['fix_team']['link_unit'][4]['plus_hp'] = $quest_requirement->fix_unit_04_link_plus_hp;
                        $data['quest_requirement']['fix_team']['link_unit'][4]['plus_atk'] = $quest_requirement->fix_unit_04_link_plus_atk;
                        $data['quest_requirement']['fix_team']['link_unit'][4]['link_point'] = $quest_requirement->fix_unit_04_link_lbs_lv;
                    }
                }
                $data['quest_requirement_obj'] = $quest_requirement;
            }
            // 1st time clear unit
            if($quest->clear_unit != 0){
                $data['quest_requirement']['clear_unit']['unit'] = $quest->clearUnit();
                $data['quest_requirement']['clear_unit']['lv'] = $quest->clear_unit_lv;
            }

            // build enemyArray
            $bossArray = array();
            $enemyArray = array();
            $questfloors = QuestFloor::where('quest_id','=',$id)->get();
            if(sizeof($questfloors) === 0){
                $data['noData'] = true;
                $data['boss'] = $quest->boss();
                for($i = 1; $i < 5; $i++){
                    if($quest->bossAbility($i)->fix_id != 0)
                        $data['abilities'][] = $quest->bossAbility($i);
                }
            }else{
                foreach($questfloors as $questfloor){
                    if($questfloor->boss_group_id != 0){
                        $bossGroup = $questfloor->bossGroup();
                        if($bossGroup != null){
            			    if ($bossGroup->enemy_id_1 != 0){$bossArray[] = $bossGroup->enemy_id_1; }
                            if ($bossGroup->enemy_id_2 != 0){$bossArray[] = $bossGroup->enemy_id_2; }
                            if ($bossGroup->enemy_id_3 != 0){$bossArray[] = $bossGroup->enemy_id_3; }
                            if ($bossGroup->enemy_id_4 != 0){$bossArray[] = $bossGroup->enemy_id_4; }
                            if ($bossGroup->enemy_id_5 != 0){$bossArray[] = $bossGroup->enemy_id_5; }
                            if ($bossGroup->enemy_id_6 != 0){$bossArray[] = $bossGroup->enemy_id_6; }
                            if ($bossGroup->enemy_id_7 != 0){$bossArray[] = $bossGroup->enemy_id_7; }

                            while($bossGroup->chain_id > 0){
                                $bossGroup = $bossGroup->chain();
                			    if ($bossGroup->enemy_id_1 != 0){$bossArray[] = $bossGroup->enemy_id_1; }
                                if ($bossGroup->enemy_id_2 != 0){$bossArray[] = $bossGroup->enemy_id_2; }
                                if ($bossGroup->enemy_id_3 != 0){$bossArray[] = $bossGroup->enemy_id_3; }
                                if ($bossGroup->enemy_id_4 != 0){$bossArray[] = $bossGroup->enemy_id_4; }
                                if ($bossGroup->enemy_id_5 != 0){$bossArray[] = $bossGroup->enemy_id_5; }
                                if ($bossGroup->enemy_id_6 != 0){$bossArray[] = $bossGroup->enemy_id_6; }
                                if ($bossGroup->enemy_id_7 != 0){$bossArray[] = $bossGroup->enemy_id_7; }
                            }
                        }
                    }
                    for($i = 1; $i<8 ;$i++){
                        $enemygroup = $questfloor->enemygroup($i);
                        if($enemygroup != null){
            			    if ($enemygroup->enemy_id_1 != 0){$enemyArray[] = $enemygroup->enemy_id_1; }
                            if ($enemygroup->enemy_id_2 != 0){$enemyArray[] = $enemygroup->enemy_id_2; }
                            if ($enemygroup->enemy_id_3 != 0){$enemyArray[] = $enemygroup->enemy_id_3; }
                            if ($enemygroup->enemy_id_4 != 0){$enemyArray[] = $enemygroup->enemy_id_4; }
                            if ($enemygroup->enemy_id_5 != 0){$enemyArray[] = $enemygroup->enemy_id_5; }
                            if ($enemygroup->enemy_id_6 != 0){$enemyArray[] = $enemygroup->enemy_id_6; }
                            if ($enemygroup->enemy_id_7 != 0){$enemyArray[] = $enemygroup->enemy_id_7; }
                        }
                    }
                }

                // boss
        		$bossArray = array_unique($bossArray);
        		$bossArray = array_values($bossArray);
                foreach($bossArray as $enemyId){
                    $temp = array();
                    $enemy = Enemy::where('fix_id','=',$enemyId)->first();
                    $temp['unit'] = $enemy->unit();
                    $temp['hp'] = $enemy->status_hp;
                    $temp['atk'] = $enemy->status_pow;
                    $temp['def'] = $enemy->status_def;
                    $temp['cd'] = $enemy->status_turn;
                    $temp['drop'] = $enemy->dropUnit();
                    if($enemy->ability1 != 0){
                        for ($j = 1; $j<5; $j++){
                            $ability = $enemy->ability($j);
                            if($ability->fix_id != 0)
                                $temp['ability'][] = $ability;
                        }
                    }
                    if($enemy->act_first != 0){
                        $temp['act_first'] = $enemy->actFirst()->getDetail($enemy->status_pow);
                    }
                    if($enemy->act_dead != 0){
                        $temp['act_dead'] = $enemy->actDead()->getDetail($enemy->status_pow);
                    }
                    if($enemy->act_table1 != 0){
                        for($i = 1; $i < 9; $i++){
                            $actTable = $enemy->actTable($i);
                            $temp2 = array();
                            for($j = 1; $j < 9; $j++){
                                $actionParam = $actTable->actionParam($j)->getDetail($enemy->status_pow);
                                if(isset($actionParam['detail'])){
                                    $temp2[] = $actionParam;
                                }
                            }
                            if(sizeof($temp2) > 0){
                                $temp['act_table'.$i]['timing_type'] = $actTable->timing_type;
                                $temp['act_table'.$i]['timing_param1'] = $actTable->timing_param1;
                                $temp['act_table'.$i]['action_type'] = $actTable->action_select_type;
                                $temp['act_table'.$i]['moves'] = $temp2;
                            }
                        }
                    }
                    $data['boss'][] = $temp;
                }

                // 小怪
        		$enemyArray = array_unique($enemyArray);
        		$enemyArray = array_values($enemyArray);
                foreach($enemyArray as $enemyId){
                    $temp = array();
                    $enemy = Enemy::where('fix_id','=',$enemyId)->first();
                    $temp['unit'] = $enemy->unit();
                    $temp['hp'] = $enemy->status_hp;
                    $temp['atk'] = $enemy->status_pow;
                    $temp['def'] = $enemy->status_def;
                    $temp['cd'] = $enemy->status_turn;
                    $temp['drop'] = $enemy->dropUnit();
                    if($enemy->act_first != 0){
                        $temp['act_first'] = $enemy->actFirst()->get()->getDetail($enemy->status_pow);
                    }
                    if($enemy->act_dead != 0){
                        $temp['act_dead'] = $enemy->actDead()->get()->getDetail($enemy->status_pow);
                    }
                    if($enemy->act_table1 != 0){
                        for($i = 1; $i < 9; $i++){
                            $actTable = $enemy->actTable($i);
                            $temp2 = array();
                            for($j = 1; $j < 9; $j++){
                                $actionParam = $actTable->actionParam($j)->getDetail($enemy->status_pow);
                                if(isset($actionParam['detail'])){
                                    $temp2[] = $actionParam;
                                }
                            }
                            if(sizeof($temp2) > 0){
                                $temp['act_table'.$i]['timing_type'] = $actTable->timing_type;
                                $temp['act_table'.$i]['timing_param1'] = $actTable->timing_param1;
                                $temp['act_table'.$i]['action_type'] = $actTable->action_select_type;
                                $temp['act_table'.$i]['moves'] = $temp2;
                            }
                        }
                    }
                    $data['enemy'][] = $temp;
                }

                // 格子分佈
                foreach($questfloors as $questfloor){
                    $floor = array();
                    for($i = 1; $i<8 ;$i++){
                        // enemy
                        $enemygroup = $questfloor->enemygroup($i);
                        if($enemygroup != null){
            			    if ($enemygroup->enemy_id_1 != 0){ $floor[$i]['enemy'][] = $enemygroup->enemy(1)->unit(); }
                            if ($enemygroup->enemy_id_2 != 0){ $floor[$i]['enemy'][] = $enemygroup->enemy(2)->unit(); }
                            if ($enemygroup->enemy_id_3 != 0){ $floor[$i]['enemy'][] = $enemygroup->enemy(3)->unit(); }
                            if ($enemygroup->enemy_id_4 != 0){ $floor[$i]['enemy'][] = $enemygroup->enemy(4)->unit(); }
                            if ($enemygroup->enemy_id_5 != 0){ $floor[$i]['enemy'][] = $enemygroup->enemy(5)->unit(); }
                            if ($enemygroup->enemy_id_6 != 0){ $floor[$i]['enemy'][] = $enemygroup->enemy(6)->unit(); }
                            if ($enemygroup->enemy_id_7 != 0){ $floor[$i]['enemy'][] = $enemygroup->enemy(7)->unit(); }
                        }
                        // trap
                        $trapgroup = $questfloor->trapgroup($i);
                        for($j = 1; $j<8 ;$j++){
                            if($trapgroup != null)
                                $floor[$i]['trap'][] = $trapgroup->panel($j);
                        }
                        // money
                        $itemgroup = $questfloor->itemgroup($i);
                        for($j = 1; $j<8 ;$j++){
                            $tempArr[] = $itemgroup->panel($j);
                        }
                        $floor[$i]['money']['min'] = min($tempArr);
                        $floor[$i]['money']['max'] = max($tempArr);
                        $floor[$i]['money']['icon'] = $quest->area()->res_icon_box;
                        // heal
                        /*$healgroup = $questfloor->healgroup($i);
                        for($j = 1; $j<8 ;$j++){
                            $floor[$i]['heal'][] = $healgroup->panel($j);
                        }*/
                    }
                    $data['floors'][] = $floor;
                }
            }
            // cache
            Cache::forever($key, $data);
        }
        return $data;
    }
    
    public function questlist(){
        $key = 'blade_questlist';
        if (Cache::has($key)){
            $data = Cache::get($key);
        } else {
            $data = array();
            $areaCate = AreaCategory::where('fix_id','!=','0')->orderBy('area_cate_type', 'asc')->orderBy('questlist_sort', 'asc')->get();
            foreach($areaCate as $cate){
                $data['areaCate'][$cate->area_cate_type][] = $cate;
                foreach($cate->getAreas() as $area){
                    $data['areaById'][$cate->fix_id][] = [$area,$area->getBoss()];
                }
            }
            // cache
            Cache::forever($key, $data);
        }
        return $data;
    }
    
    public function rank(){
        $key = 'blade_rank';
        if (Cache::has($key)){
            $data = Cache::get($key);
        } else {
            $rank = UserRank::where('fix_id','!=','0')->get();
            $data = compact('rank');
            // cache
            Cache::forever($key, $data);
        }
        return $data;
    }

    public function skill($type){
        $key = 'api_skill_'.$type;
        if (Cache::has($key)){
            $data = Cache::get($key);
        } else {
            switch($type){
                case 'n':
                case 'ln':
                    $skills = NSkill::where('fix_id','!=','0')->get();
                    foreach($skills as $skill){
                        $obj = new \stdClass;
                        if($type == 'ln')
                            $obj->units = $skill->minLinkUnits();
                        else
                            $obj->units = $skill->minUnits();
                        if(sizeof($obj->units) == 0){continue;}
                        $obj->name = $skill->name;
                        $obj->detail = $skill->detail;
                        $obj->detailCn = $skill->getDetailCn();
                        foreach ($obj->units as $unit) {
                            $unit->image = $this->imageUtil->getIconLink($this->function->getTriId($unit->draw_id));
                        }
                        if($type == 'n'){
                            $cardDesc = "";
                            $cards = $skill->getCard();
                            for($i = 4; $i >= 0; $i--){
                                if($cards[$i] == 0)
                                    unset($cards[$i]);
                                else
                                    $cardDesc .= $this->function->getElement($cards[$i])."板 ";
                            }
                            $obj->card = $cards;
                            $cardDesc .= sizeof($obj->card)."板 ";
                            if(sizeof(array_unique($obj->card)) > 1)
                                $cardDesc .= "雜色";
                            else
                                $cardDesc .= "單色";
                            $obj->cardDesc = $cardDesc;
                        }
                        switch($skill->skill_type){
                            case 1:
                                $data['skill'][1][] = $obj;
                                break;
                            case 2:
                                $data['skill'][2][] = $obj;
                                break;
                            case 5:
                                $data['skill'][3][] = $obj;
                                break;
                        }
                    }
                    $data['status'] = 1;
                    break;
                case 'l':
                    $skills = LSkill::where('fix_id','!=','0')->get();
                    foreach($skills as $skill){
                        $obj = new \stdClass;
                        $obj->units = $skill->minUnits();
                        if(sizeof($obj->units) == 0){continue;}
                        $obj->name = $skill->name;
                        $obj->detail = $skill->detail;
                        $obj->detailCn = $skill->getDetailCn();
                        foreach ($obj->units as $unit) {
                            $unit->image = $this->imageUtil->getIconLink($this->function->getTriId($unit->draw_id));
                        }
                        if($skill->skill_powup_elem_status == 1 || $skill->skill_powup_kind_status == 1)
                            $data['skill'][1][] = $obj;
                        elseif ($skill->skill_powup_elem_status == 2 || $skill->skill_powup_kind_status == 2)
                            $data['skill'][2][] = $obj;
                        elseif ($skill->skill_powup_elem_status == 3 || $skill->skill_powup_kind_status == 3)
                            $data['skill'][3][] = $obj;
                        elseif ($skill->skill_follow_atk_active == 2 )
                            $data['skill'][4][] = $obj;
                        elseif ($skill->skill_decline_dmg_active == 2)
                            $data['skill'][5][] = $obj;
                        elseif ($skill->skill_recovery_move_active == 2 || $skill->skill_recovery_battle_active == 2)
                            $data['skill'][6][] = $obj;
                        elseif ($skill->skill_quick_time_active == 2)
                            $data['skill'][7][] = $obj;
                        elseif ($skill->skill_recovery_support_active == 2)
                            $data['skill'][8][] = $obj;
                        elseif ($skill->skill_recovery_atk_active == 2)
                            $data['skill'][9][] = $obj;
                        elseif ($skill->skill_recovery_battle_active == 2)
                            $data['skill'][10][] = $obj;
                        elseif ($skill->skill_hpfull_powup_active == 2 || $skill->skill_hpdown_powup_active == 2)
                            $data['skill'][11][] = $obj;
                        elseif ($skill->skill_mekuri_powup_active == 2)
                            $data['skill'][12][] = $obj;
                        elseif ($skill->skill_funbari_active == 2)
                            $data['skill'][13][] = $obj;
                        elseif ($skill->skill_hpfull_guard_active == 2)
                            $data['skill'][14][] = $obj;
                        elseif ($skill->skill_initiative_atk_active == 2)
                            $data['skill'][15][] = $obj;
                        elseif ($skill->skill_transform_card_active == 2)
                            $data['skill'][16][] = $obj;
                        elseif ($skill->skill_damageup_color_active == 2 || $skill->skill_damageup_hands_active == 2)
                            $data['skill'][17][] = $obj;
                        else{
                            switch($skill->skill_type){
                                case 2:
                                    $data['skill'][17][] = $obj;
                                    break;
                                case 3:
                                    $data['skill'][18][] = $obj;
                                    break;
                                case 5:
                                    $data['skill'][19][] = $obj;
                                    break;
                            }
                        }
                    }
                    $data['status'] = 1;
                    break;
                case 'a':
                    $skills = ASkill::where('fix_id','!=','0')->get();
                    foreach($skills as $skill){
                        $obj = new \stdClass;
                        $obj->units = $skill->minUnits();
                        if(sizeof($obj->units) == 0){continue;}
                        $obj->name = $skill->name;
                        $obj->detail = $skill->detail;
                        $obj->detailCn = $skill->getDetailCn();
                        foreach ($obj->units as $unit) {
                            $unit->image = $this->imageUtil->getIconLink($this->function->getTriId($unit->draw_id));
                        }
                        switch($skill->skill_cate){
                            case 2:
                                $data['skill'][1][] = $obj;
                                break;
                            case 4:
                                $data['skill'][2][] = $obj;
                                break;
                            case 5:
                                $data['skill'][3][] = $obj;
                                break;
                            case 6:
                            case 9:
                                $data['skill'][4][] = $obj;
                                break;
                            case 7:
                                $data['skill'][5][] = $obj;
                                break;
                            case 8:
                            case 11:
                                $data['skill'][6][] = $obj;
                                break;
                            case 3:
                            case 10:
                                $data['skill'][7][] = $obj;
                                break;
                            case 12:
                                $data['skill'][8][] = $obj;
                                break;
                            case 13:
                                $data['skill'][9][] = $obj;
                                break;
                            case 14:
                                $data['skill'][10][] = $obj;
                                break;
                            case 15:
                                $data['skill'][11][] = $obj;
                                break;
                            case 16:
                                $data['skill'][12][] = $obj;
                                break;
                            case 17:
                            case 18:
                            case 40:
                            case 43:
                                $data['skill'][13][] = $obj;
                                break;
                            case 19:
                                $data['skill'][14][] = $obj;
                                break;
                            case 20:
                                $data['skill'][15][] = $obj;
                                break;
                            case 21:
                                $data['skill'][16][] = $obj;
                                break;
                            case 22:
                            case 23:
                            case 24:
                            case 25:
                            case 41:
                                $data['skill'][17][] = $obj;
                                break;
                            case 28:
                                $data['skill'][18][] = $obj;
                                break;
                            case 29:
                                $data['skill'][19][] = $obj;
                                break;
                            case 30:
                                $data['skill'][20][] = $obj;
                                break;
                            case 44:
                                $data['skill'][21][] = $obj;
                                break;
                            case 45:
                                $data['skill'][22][] = $obj;
                                break;
                            case 42:
                            case 46:
                                $data['skill'][23][] = $obj;
                                break;
                            case 47:
                                $data['skill'][24][] = $obj;
                                break;
                            case 49:
                                $data['skill'][25][] = $obj;
                                break;
                        }
                    }
                    $data['status'] = 1;
                    break;
                case 'p':
                case 'lp':
                    $skills = PSkill::where('fix_id','!=','0')->get();
                    foreach($skills as $skill){
                        $obj = new \stdClass;
                        if($type == 'lp')
                            $obj->units = $skill->minLinkUnits();
                        else
                            $obj->units = $skill->minUnits();
                        if(sizeof($obj->units) == 0){continue;}
                        $obj->name = $skill->name;
                        $obj->detail = $skill->detail;
                        $obj->detailCn = $skill->getDetailCn();

                        foreach ($obj->units as $unit) {
                            $unit->image = $this->imageUtil->getIconLink($this->function->getTriId($unit->draw_id));
                        }
                        if($skill->skill_trap_pass_active == 2)
                            $data['skill'][1][] = $obj;
                        elseif ($skill->skill_powup_kind_active == 2)
                            $data['skill'][2][] = $obj;
                        elseif ($skill->skill_counter_atk_active == 2)
                            $data['skill'][3][] = $obj;
                        elseif ($skill->skill_damage_recovery_active == 2 )
                            $data['skill'][4][] = $obj;
                        elseif ($skill->skill_hp_full_powup_active == 2 || $skill->skill_dying_powup_active == 2)
                            $data['skill'][5][] = $obj;
                        elseif ($skill->skill_backatk_pass_active == 2)
                            $data['skill'][6][] = $obj;
                        elseif ($skill->skill_decline_dmg_elem_active == 2 || $skill->skill_decline_dmg_kind_active == 2)
                            $data['skill'][7][] = $obj;
                        elseif ($skill->skill_boost_chance_active == 2)
                            $data['skill'][8][] = $obj;
                        else{
                            switch($skill->skill_type){
                                case 1:
                                    $data['skill'][9][] = $obj;
                                    break;
                                case 3:
                                    $data['skill'][10][] = $obj;
                                    break;
                                case 4:
                                    $data['skill'][11][] = $obj;
                                    break;
                                case 5:
                                    $data['skill'][12][] = $obj;
                                    break;
                                case 6:
                                    $data['skill'][13][] = $obj;
                                    break;
                                case 7:
                                    $data['skill'][14][] = $obj;
                                    break;
                                case 9:
                                    $data['skill'][15][] = $obj;
                                    break;
                                case 10:
                                    $data['skill'][16][] = $obj;
                                    break;
                                case 11:
                                    $data['skill'][17][] = $obj;
                                    break;
                                case 13:
                                    $data['skill'][18][] = $obj;
                                    break;
                                case 14:
                                    $data['skill'][19][] = $obj;
                                    break;
                                case 15:
                                    $data['skill'][20][] = $obj;
                                    break;
                            }
                        }
                    }
                    $data['status'] = 1;
                    break;
                default:
                    $data['status'] = 0;
                    break;
            }
            $data['type'] = $type;
            // cache
            if($data['status'] == 1)
                Cache::forever($key, $data);
        }
        return $data;
    }
    
    public function story(){
        $key = 'blade_story';
        if (Cache::has($key)){
            $data = Cache::get($key);
        } else {
            $data = [];
            $unitlist = Unit::where('fix_id','!=','0')->orderBy('draw_id')->get();
            foreach ($unitlist as $unit) {
                $obj = [];
                $obj['name'] = $unit->name;
                $obj['draw_id'] = $unit->draw_id;
                $obj['series'] = $unit->series;
                $obj['detail'] = $unit->detail;
                $translate = $unit->getTranslate();
                $obj['detailcn'] = !is_null($translate) ? $translate->text : "";
                $obj['image'] = $this->imageUtil->getIconLink($this->function->getTriId($unit->draw_id));
                $data['unit'][] = $obj;
            }
			$areaCate = AreaCategory::where('fix_id','!=','0')->orderBy('area_cate_type', 'asc')->orderBy('questlist_sort', 'asc')->get();
            foreach($areaCate as $cate){
                foreach($cate->getAreas() as $area){
					$questlist = Quest::where('area_id','=',$area->fix_id)->get();
					foreach ($questlist as $quest) {
						$obj = [];
						$obj['area_cate_id'] = $cate->fix_id;
						$obj['area_cate_name'] = $cate->area_cate_name;
						$obj['area_cate_type'] = $cate->area_cate_type;
						$obj['quest_id'] = $quest->fix_id;
						$obj['quest_name'] = $quest->quest_name;
						$obj['story'] = $quest->story;
						$translate = $quest->getTranslate();
						$obj['storycn'] = !is_null($translate) ? $translate->text : "";
						$obj['image'] = $this->imageUtil->getIconLink($this->function->getTriId($quest->boss()->draw_id));
						$data['quest'][] = $obj;
					}
                }
            }
            // cache
            Cache::forever($key, $data);
        }
        return $data;
    }
    
    public function unit($id){
        $key = 'blade_unit'.$id;
        if (Cache::has($key)){
            $data = Cache::get($key);
        } else {
            $unit = Unit::where('draw_id','=',$id)->firstOrFail();
            $data = compact('unit');

            $detailcn = $unit->getTranslate();
            if($detailcn !== null)
                $data['detailcn'] = $detailcn->text;

            if($unit->skill_leader != 0){
                $ls = $unit->getSkill('ls');
                $data['lsName'] = $ls->name;
                $data['lsDetail'] = $ls->detail;
                $data['lsDetailCn'] = $ls->getDetailCn();
            }
            if($unit->skill_limitbreak != 0){
                $as = $unit->getSkill('as');
                $data['asName'] = $as->name;
                $data['asDetail'] = $as->detail;
                $data['asDetailCn'] = $as->getDetailCn();
                $data['asMax'] = $as->use_turn;
                $data['asMin'] = $as->use_turn - $as->level_max;
                $data['sameAS'] = $as->units();
            }
            $ns1 = $unit->getSkill('ns1');
            $data['ns1Name'] = $ns1->name;
            $data['ns1Detail'] = $ns1->detail;
            $data['ns1DetailCn'] = $ns1->getDetailCn();
            $data['ns1Card'] = $ns1->getCard();
            if($unit->skill_active1 != 0){
                $ns2 = $unit->getSkill('ns2');
                $data['ns2Name'] = $ns2->name;
                $data['ns2Detail'] = $ns2->detail;
                $data['ns2DetailCn'] = $ns2->getDetailCn();
                $data['ns2Card'] = $ns2->getCard();
            }
            if($unit->skill_passive != 0){
                $ps = $unit->getSkill('ps');
                $data['psName'] = $ps->name;
                $data['psDetail'] = $ps->detail;
                $data['psDetailCn'] = $ps->getDetailCn();
            }
            if($unit->link_enable == 2){
                $link = LinkSystem::where('elem','=',$unit->element)->firstOrFail();
                $data['link'] = $link;
                $data['link_hp'] = $link->hp;
                $data['link_atk'] = $link->atk;

                $data['race_bouns'] = $this->function->linkRaceBonus($unit->rare, $unit->kind, $unit->sub_kind);

                if($unit->link_skill_active != 0){
                    $lns = $unit->getSkill('lns');
                    $data['lnsName'] = $lns->name;
                    $data['lnsDetail'] = $lns->detail;
                    $data['lnsDetailCn'] = $lns->getDetailCn();
                    $data['lnsOdds'] = $lns->skill_link_odds;
                }
                if($unit->link_skill_passive != 0){
                    $lps = $unit->getSkill('lps');
                    $data['lpsName'] = $lps->name;
                    $data['lpsDetail'] = $lps->detail;
                    $data['lpsDetailCn'] = $lps->getDetailCn();
                }

                $data['linkPart'][0] = $unit->linkPart1();
                $data['linkPart'][1] = $unit->linkPart2();
                $data['linkPart'][2] = $unit->linkPart3();
                $data['delLinkPart'][0] = $unit->delLinkPart1();
                $data['delLinkPart'][1] = $unit->delLinkPart2();
                $data['delLinkPart'][2] = $unit->delLinkPart3();
            }
            $limit_over = $unit->limitOver();
            if($limit_over != null){
                $data['limit_over_max'] = $limit_over->limit_over_max;
                $data['limit_over_max_hp'] = $limit_over->limit_over_max_hp;
                $data['limit_over_max_atk'] = $limit_over->limit_over_max_atk;
                $data['limit_over_max_cost'] = $limit_over->limit_over_max_cost;
                $data['limit_over_max_charm'] = $limit_over->limit_over_max_charm;
                $data['limit_grow'] = $limit_over->limit_grow;
            } else {
                $data['limit_over_max'] = 0;
                $data['limit_over_max_hp'] = 0;
                $data['limit_over_max_atk'] =0;
                $data['limit_over_max_cost'] = 0;
                $data['limit_over_max_charm'] = 0;
                $data['limit_grow'] = 0;
            }
            // arrays
            $data['evos'] = Evo::where('unit_id_parts1','=',$unit->fix_id)->orWhere('unit_id_parts2','=',$unit->fix_id)->orWhere('unit_id_parts3','=',$unit->fix_id)->orWhere('unit_id_parts4','=',$unit->fix_id)->get();
            $data['areas'] = $this->function->getArea($unit);
            $data['evoFrom'] = Evo::where('unit_id_after','=',$unit->fix_id)->first();
            $data['evoTo'] = Evo::where('unit_id_pre','=',$unit->fix_id)->first();

            // cache
            Cache::forever($key, $data);
        }
        return $data;
    }

    public function unitlist(){
        $key = 'blade_unitlist';
        if (Cache::has($key)){
            $unitlist = Cache::get($key);
        } else {
            $unitlist = DB::table('unit')->select('fix_id','name','draw_id','element','kind','sub_kind','rare','series','link_enable')->where('fix_id','!=','0')->orderBy('draw_id')->get();
            foreach ($unitlist as $unit) {
                $unit->image = $this->imageUtil->getIconLink($this->function->getTriId($unit->draw_id));
            }
            // cache
            Cache::forever($key, $unitlist);
        }
        return $unitlist;
    }
    
    public function voteResult($id){
        $key = 'blade_vote_result_'.$id;
        if (Cache::has($key)){
            $data = Cache::get($key);
        } else {
            switch ($id) {
                case 1:
                    $data = array(
                        array('rank' => '1', 'unit' => Unit::where('draw_id','=','148')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '2', 'unit' => Unit::where('draw_id','=','146')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '3', 'unit' => Unit::where('draw_id','=','150')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '4', 'unit' => Unit::where('draw_id','=','296')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '5', 'unit' => Unit::where('draw_id','=','12')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '6', 'unit' => Unit::where('draw_id','=','262')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '7', 'unit' => Unit::where('draw_id','=','8')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '8', 'unit' => Unit::where('draw_id','=','20')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '9', 'unit' => Unit::where('draw_id','=','264')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '10', 'unit' => Unit::where('draw_id','=','260')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '11', 'unit' => Unit::where('draw_id','=','16')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '12', 'unit' => Unit::where('draw_id','=','4')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '13', 'unit' => Unit::where('draw_id','=','343')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '14', 'unit' => Unit::where('draw_id','=','156')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '15', 'unit' => Unit::where('draw_id','=','154')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '16', 'unit' => Unit::where('draw_id','=','152')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '17', 'unit' => Unit::where('draw_id','=','274')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '18', 'unit' => Unit::where('draw_id','=','286')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '19', 'unit' => Unit::where('draw_id','=','268')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '20', 'unit' => Unit::where('draw_id','=','220')->first(), 'type' => '', 'change' => ''),
                    );
                    break;
                case 2:
                    $data = array(
                        array('rank' => '1', 'unit' => Unit::where('draw_id','=','296')->first(), 'type' => 'up', 'change' => '3'),
                        array('rank' => '2', 'unit' => Unit::where('draw_id','=','148')->first(), 'type' => 'down', 'change' => '1'),
                        array('rank' => '3', 'unit' => Unit::where('draw_id','=','146')->first(), 'type' => 'down', 'change' => '1'),
                        array('rank' => '4', 'unit' => Unit::where('draw_id','=','152')->first(), 'type' => 'up', 'change' => '12'),
                        array('rank' => '5', 'unit' => Unit::where('draw_id','=','274')->first(), 'type' => 'up', 'change' => '12'),
                        array('rank' => '6', 'unit' => Unit::where('draw_id','=','428')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '7', 'unit' => Unit::where('draw_id','=','436')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '8', 'unit' => Unit::where('draw_id','=','150')->first(), 'type' => 'down', 'change' => '5'),
                        array('rank' => '9', 'unit' => Unit::where('draw_id','=','8')->first(), 'type' => 'down', 'change' => '2'),
                        array('rank' => '10', 'unit' => Unit::where('draw_id','=','262')->first(), 'type' => 'down', 'change' => '3'),
                        array('rank' => '11', 'unit' => Unit::where('draw_id','=','474')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '12', 'unit' => Unit::where('draw_id','=','218')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '13', 'unit' => Unit::where('draw_id','=','264')->first(), 'type' => 'down', 'change' => '4'),
                        array('rank' => '14', 'unit' => Unit::where('draw_id','=','260')->first(), 'type' => 'down', 'change' => '4'),
                        array('rank' => '15', 'unit' => Unit::where('draw_id','=','482')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '16', 'unit' => Unit::where('draw_id','=','20')->first(), 'type' => 'down', 'change' => '8'),
                        array('rank' => '17', 'unit' => Unit::where('draw_id','=','434')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '18', 'unit' => Unit::where('draw_id','=','343')->first(), 'type' => 'down', 'change' => '5'),
                        array('rank' => '19', 'unit' => Unit::where('draw_id','=','534')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '20', 'unit' => Unit::where('draw_id','=','426')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '21', 'unit' => Unit::where('draw_id','=','12')->first(), 'type' => 'down', 'change' => '16'),
                        array('rank' => '22', 'unit' => Unit::where('draw_id','=','355')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '23', 'unit' => Unit::where('draw_id','=','424')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '24', 'unit' => Unit::where('draw_id','=','154')->first(), 'type' => 'down', 'change' => '9'),
                        array('rank' => '25', 'unit' => Unit::where('draw_id','=','16')->first(), 'type' => 'down', 'change' => '14'),
                        array('rank' => '26', 'unit' => Unit::where('draw_id','=','156')->first(), 'type' => 'down', 'change' => '12'),
                        array('rank' => '27', 'unit' => Unit::where('draw_id','=','286')->first(), 'type' => 'down', 'change' => '9'),
                        array('rank' => '28', 'unit' => Unit::where('draw_id','=','4')->first(), 'type' => 'down', 'change' => '16'),
                        array('rank' => '29', 'unit' => Unit::where('draw_id','=','333')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '30', 'unit' => Unit::where('draw_id','=','432')->first(), 'type' => 'new', 'change' => ''),
                    );
                    break;
                case 3:
                    $data = array(
                        array('rank' => '1', 'unit' => Unit::where('draw_id','=','650')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '2', 'unit' => Unit::where('draw_id','=','296')->first(), 'type' => 'down', 'change' => '1'),
                        array('rank' => '3', 'unit' => Unit::where('draw_id','=','148')->first(), 'type' => 'down', 'change' => '1'),
                        array('rank' => '4', 'unit' => Unit::where('draw_id','=','610')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '5', 'unit' => Unit::where('draw_id','=','146')->first(), 'type' => 'down', 'change' => '2'),
                        array('rank' => '6', 'unit' => Unit::where('draw_id','=','559')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '7', 'unit' => Unit::where('draw_id','=','595')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '8', 'unit' => Unit::where('draw_id','=','436')->first(), 'type' => 'down', 'change' => '1'),
                        array('rank' => '9', 'unit' => Unit::where('draw_id','=','8')->first(), 'type' => 'equal', 'change' => ''),
                        array('rank' => '10', 'unit' => Unit::where('draw_id','=','482')->first(), 'type' => 'up', 'change' => '5'),
                        array('rank' => '11', 'unit' => Unit::where('draw_id','=','682')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '12', 'unit' => Unit::where('draw_id','=','474')->first(), 'type' => 'down', 'change' => '3'),
                        array('rank' => '13', 'unit' => Unit::where('draw_id','=','20')->first(), 'type' => 'up', 'change' => '3'),
                        array('rank' => '14', 'unit' => Unit::where('draw_id','=','636')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '15', 'unit' => Unit::where('draw_id','=','152')->first(), 'type' => 'down', 'change' => '11'),
                        array('rank' => '16', 'unit' => Unit::where('draw_id','=','12')->first(), 'type' => 'up', 'change' => '5'),
                        array('rank' => '17', 'unit' => Unit::where('draw_id','=','150')->first(), 'type' => 'down', 'change' => '9'),
                        array('rank' => '18', 'unit' => Unit::where('draw_id','=','262')->first(), 'type' => 'down', 'change' => '9'),
                        array('rank' => '19', 'unit' => Unit::where('draw_id','=','274')->first(), 'type' => 'down', 'change' => '14'),
                        array('rank' => '20', 'unit' => Unit::where('draw_id','=','678')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '21', 'unit' => Unit::where('draw_id','=','428')->first(), 'type' => 'down', 'change' => '15'),
                        array('rank' => '22', 'unit' => Unit::where('draw_id','=','218')->first(), 'type' => 'down', 'change' => '10'),
                        array('rank' => '23', 'unit' => Unit::where('draw_id','=','264')->first(), 'type' => 'down', 'change' => '9'),
                        array('rank' => '24', 'unit' => Unit::where('draw_id','=','343')->first(), 'type' => 'down', 'change' => '6'),
                        array('rank' => '25', 'unit' => Unit::where('draw_id','=','336')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '26', 'unit' => Unit::where('draw_id','=','648')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '27', 'unit' => Unit::where('draw_id','=','4')->first(), 'type' => 'up', 'change' => '1'),
                        array('rank' => '28', 'unit' => Unit::where('draw_id','=','286')->first(), 'type' => 'down', 'change' => '1'),
                        array('rank' => '29', 'unit' => Unit::where('draw_id','=','278')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '30', 'unit' => Unit::where('draw_id','=','426')->first(), 'type' => 'down', 'change' => '10'),
                        array('rank' => '31', 'unit' => Unit::where('draw_id','=','355')->first(), 'type' => 'down', 'change' => '8'),
                        array('rank' => '32', 'unit' => Unit::where('draw_id','=','154')->first(), 'type' => 'down', 'change' => '8'),
                        array('rank' => '33', 'unit' => Unit::where('draw_id','=','260')->first(), 'type' => 'down', 'change' => '19'),
                        array('rank' => '34', 'unit' => Unit::where('draw_id','=','347')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '35', 'unit' => Unit::where('draw_id','=','351')->first(), 'type' => 'down', 'change' => '11'),
                        array('rank' => '36', 'unit' => Unit::where('draw_id','=','16')->first(), 'type' => 'down', 'change' => '9'),
                        array('rank' => '37', 'unit' => Unit::where('draw_id','=','156')->first(), 'type' => 'down', 'change' => '9'),
                        array('rank' => '38', 'unit' => Unit::where('draw_id','=','333')->first(), 'type' => 'down', 'change' => '22'),
                        array('rank' => '39', 'unit' => Unit::where('draw_id','=','434')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '40', 'unit' => Unit::where('draw_id','=','630')->first(), 'type' => 'new', 'change' => ''),
                    );
                    break;
                case 4:
                    $data = array(
                        array('rank' => '1', 'unit' => Unit::where('draw_id','=','650')->first(), 'type' => 'equal', 'change' => ''),
                        array('rank' => '2', 'unit' => Unit::where('draw_id','=','750')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '3', 'unit' => Unit::where('draw_id','=','296')->first(), 'type' => 'down', 'change' => '1'),
                        array('rank' => '4', 'unit' => Unit::where('draw_id','=','610')->first(), 'type' => 'equal', 'change' => ''),
                        array('rank' => '5', 'unit' => Unit::where('draw_id','=','559')->first(), 'type' => 'up', 'change' => '1'),
                        array('rank' => '6', 'unit' => Unit::where('draw_id','=','148')->first(), 'type' => 'down', 'change' => '3'),
                        array('rank' => '7', 'unit' => Unit::where('draw_id','=','595')->first(), 'type' => 'equal', 'change' => ''),
                        array('rank' => '8', 'unit' => Unit::where('draw_id','=','436')->first(), 'type' => 'equal', 'change' => ''),
                        array('rank' => '9', 'unit' => Unit::where('draw_id','=','262')->first(), 'type' => 'up', 'change' => '9'),
                        array('rank' => '10', 'unit' => Unit::where('draw_id','=','682')->first(), 'type' => 'up', 'change' => '1'),
                        array('rank' => '11', 'unit' => Unit::where('draw_id','=','778')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '12', 'unit' => Unit::where('draw_id','=','678')->first(), 'type' => 'up', 'change' => '8'),
                        array('rank' => '13', 'unit' => Unit::where('draw_id','=','768')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '14', 'unit' => Unit::where('draw_id','=','146')->first(), 'type' => 'down', 'change' => '9'),
                        array('rank' => '15', 'unit' => Unit::where('draw_id','=','762')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '16', 'unit' => Unit::where('draw_id','=','752')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '17', 'unit' => Unit::where('draw_id','=','474')->first(), 'type' => 'down', 'change' => '5'),
                        array('rank' => '18', 'unit' => Unit::where('draw_id','=','8')->first(), 'type' => 'down', 'change' => '9'),
                        array('rank' => '19', 'unit' => Unit::where('draw_id','=','766')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '20', 'unit' => Unit::where('draw_id','=','333')->first(), 'type' => 'up', 'change' => '18'),
                        array('rank' => '21', 'unit' => Unit::where('draw_id','=','482')->first(), 'type' => 'down', 'change' => '11'),
                        array('rank' => '22', 'unit' => Unit::where('draw_id','=','764')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '23', 'unit' => Unit::where('draw_id','=','12')->first(), 'type' => 'down', 'change' => '7'),
                        array('rank' => '24', 'unit' => Unit::where('draw_id','=','770')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '25', 'unit' => Unit::where('draw_id','=','20')->first(), 'type' => 'down', 'change' => '12'),
                        array('rank' => '26', 'unit' => Unit::where('draw_id','=','218')->first(), 'type' => 'down', 'change' => '4'),
                        array('rank' => '27', 'unit' => Unit::where('draw_id','=','150')->first(), 'type' => 'down', 'change' => '10'),
                        array('rank' => '28', 'unit' => Unit::where('draw_id','=','152')->first(), 'type' => 'down', 'change' => '13'),
                        array('rank' => '29', 'unit' => Unit::where('draw_id','=','336')->first(), 'type' => 'down', 'change' => '4'),
                        array('rank' => '30', 'unit' => Unit::where('draw_id','=','772')->first(), 'type' => 'new', 'change' => ''),
                    );
                    break;
                case 5:
                    $data = array(
                        array('rank' => '1', 'unit' => Unit::where('draw_id','=','866')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '2', 'unit' => Unit::where('draw_id','=','650')->first(), 'type' => 'down', 'change' => '1'),
                        array('rank' => '3', 'unit' => Unit::where('draw_id','=','965')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '4', 'unit' => Unit::where('draw_id','=','898')->first(), 'type' => 'up', 'change' => '21'),
                        array('rank' => '5', 'unit' => Unit::where('draw_id','=','896')->first(), 'type' => 'up', 'change' => '18'),
                        array('rank' => '6', 'unit' => Unit::where('draw_id','=','895')->first(), 'type' => 'up', 'change' => '12'),
                        array('rank' => '7', 'unit' => Unit::where('draw_id','=','897')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '8', 'unit' => Unit::where('draw_id','=','610')->first(), 'type' => 'down', 'change' => '4'),
                        array('rank' => '9', 'unit' => Unit::where('draw_id','=','750')->first(), 'type' => 'down', 'change' => '7'),
                        array('rank' => '10', 'unit' => Unit::where('draw_id','=','296')->first(), 'type' => 'down', 'change' => '7'),
                        array('rank' => '11', 'unit' => Unit::where('draw_id','=','894')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '12', 'unit' => Unit::where('draw_id','=','963')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '13', 'unit' => Unit::where('draw_id','=','148')->first(), 'type' => 'down', 'change' => '7'),
                        array('rank' => '14', 'unit' => Unit::where('draw_id','=','682')->first(), 'type' => 'down', 'change' => '4'),
                        array('rank' => '15', 'unit' => Unit::where('draw_id','=','559')->first(), 'type' => 'down', 'change' => '10'),
                        array('rank' => '16', 'unit' => Unit::where('draw_id','=','927')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '17', 'unit' => Unit::where('draw_id','=','678')->first(), 'type' => 'down', 'change' => '5'),
                        array('rank' => '18', 'unit' => Unit::where('draw_id','=','762')->first(), 'type' => 'down', 'change' => '3'),
                        array('rank' => '19', 'unit' => Unit::where('draw_id','=','766')->first(), 'type' => 'equal', 'change' => ''),
                        array('rank' => '20', 'unit' => Unit::where('draw_id','=','778')->first(), 'type' => 'down', 'change' => '9'),
                        array('rank' => '21', 'unit' => Unit::where('draw_id','=','768')->first(), 'type' => 'down', 'change' => '8'),
                        array('rank' => '22', 'unit' => Unit::where('draw_id','=','811')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '23', 'unit' => Unit::where('draw_id','=','436')->first(), 'type' => 'down', 'change' => '15'),
                        array('rank' => '24', 'unit' => Unit::where('draw_id','=','474')->first(), 'type' => 'down', 'change' => '7'),
                        array('rank' => '25', 'unit' => Unit::where('draw_id','=','146')->first(), 'type' => 'down', 'change' => '11'),
                        array('rank' => '26', 'unit' => Unit::where('draw_id','=','764')->first(), 'type' => 'down', 'change' => '4'),
                        array('rank' => '27', 'unit' => Unit::where('draw_id','=','752')->first(), 'type' => 'down', 'change' => '11'),
                        array('rank' => '28', 'unit' => Unit::where('draw_id','=','899')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '29', 'unit' => Unit::where('draw_id','=','770')->first(), 'type' => 'down', 'change' => '5'),
                        array('rank' => '30', 'unit' => Unit::where('draw_id','=','772')->first(), 'type' => 'equal', 'change' => ''),
                        array('rank' => '31', 'unit' => Unit::where('draw_id','=','482')->first(), 'type' => 'down', 'change' => '10'),
                        array('rank' => '32', 'unit' => Unit::where('draw_id','=','218')->first(), 'type' => 'down', 'change' => '6'),
                        array('rank' => '33', 'unit' => Unit::where('draw_id','=','636')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '34', 'unit' => Unit::where('draw_id','=','262')->first(), 'type' => 'down', 'change' => '25'),
                        array('rank' => '35', 'unit' => Unit::where('draw_id','=','428')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '36', 'unit' => Unit::where('draw_id','=','879')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '37', 'unit' => Unit::where('draw_id','=','595')->first(), 'type' => 'down', 'change' => '30'),
                        array('rank' => '38', 'unit' => Unit::where('draw_id','=','274')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '39', 'unit' => Unit::where('draw_id','=','355')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '40', 'unit' => Unit::where('draw_id','=','864')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '41', 'unit' => Unit::where('draw_id','=','817')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '42', 'unit' => Unit::where('draw_id','=','819')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '43', 'unit' => Unit::where('draw_id','=','150')->first(), 'type' => 'down', 'change' => '16'),
                        array('rank' => '44', 'unit' => Unit::where('draw_id','=','343')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '45', 'unit' => Unit::where('draw_id','=','152')->first(), 'type' => 'down', 'change' => '17'),
                        array('rank' => '46', 'unit' => Unit::where('draw_id','=','278')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '47', 'unit' => Unit::where('draw_id','=','336')->first(), 'type' => 'down', 'change' => '18'),
                        array('rank' => '48', 'unit' => Unit::where('draw_id','=','264')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '49', 'unit' => Unit::where('draw_id','=','628')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '50', 'unit' => Unit::where('draw_id','=','154')->first(), 'type' => 'new', 'change' => ''),
                    );
                    break;
                case 6:
                    $data = array(
                        array('rank' => '1', 'unit' => Unit::where('draw_id','=','1011')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '2', 'unit' => Unit::where('draw_id','=','866')->first(), 'type' => 'down', 'change' => '1'),
                        array('rank' => '3', 'unit' => Unit::where('draw_id','=','650')->first(), 'type' => 'down', 'change' => '1'),
                        array('rank' => '4', 'unit' => Unit::where('draw_id','=','296')->first(), 'type' => 'up', 'change' => '6'),
                        array('rank' => '5', 'unit' => Unit::where('draw_id','=','965')->first(), 'type' => 'down', 'change' => '2'),
                        array('rank' => '6', 'unit' => Unit::where('draw_id','=','1012')->first(), 'type' => 'up', 'change' => '40'),
                        array('rank' => '7', 'unit' => Unit::where('draw_id','=','610')->first(), 'type' => 'up', 'change' => '1'),
                        array('rank' => '8', 'unit' => Unit::where('draw_id','=','750')->first(), 'type' => 'up', 'change' => '1'),
                        array('rank' => '9', 'unit' => Unit::where('draw_id','=','898')->first(), 'type' => 'down', 'change' => '1'),
                        array('rank' => '10', 'unit' => Unit::where('draw_id','=','897')->first(), 'type' => 'down', 'change' => '3'),
                        array('rank' => '11', 'unit' => Unit::where('draw_id','=','963')->first(), 'type' => 'up', 'change' => '1'),
                        array('rank' => '12', 'unit' => Unit::where('draw_id','=','1013')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '13', 'unit' => Unit::where('draw_id','=','896')->first(), 'type' => 'down', 'change' => '8'),
                        array('rank' => '14', 'unit' => Unit::where('draw_id','=','1004')->first(), 'type' => 'up', 'change' => '18'),
                        array('rank' => '15', 'unit' => Unit::where('draw_id','=','895')->first(), 'type' => 'down', 'change' => '9'),
                        array('rank' => '16', 'unit' => Unit::where('draw_id','=','595')->first(), 'type' => 'up', 'change' => '21'),
                        array('rank' => '17', 'unit' => Unit::where('draw_id','=','778')->first(), 'type' => 'up', 'change' => '3'),
                        array('rank' => '18', 'unit' => Unit::where('draw_id','=','894')->first(), 'type' => 'down', 'change' => '7'),
                        array('rank' => '19', 'unit' => Unit::where('draw_id','=','559')->first(), 'type' => 'down', 'change' => '4'),
                        array('rank' => '20', 'unit' => Unit::where('draw_id','=','148')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '21', 'unit' => Unit::where('draw_id','=','474')->first(), 'type' => 'up', 'change' => '3'),
                        array('rank' => '22', 'unit' => Unit::where('draw_id','=','436')->first(), 'type' => 'up', 'change' => '1'),
                        array('rank' => '23', 'unit' => Unit::where('draw_id','=','682')->first(), 'type' => 'down', 'change' => '9'),
                        array('rank' => '24', 'unit' => Unit::where('draw_id','=','762')->first(), 'type' => 'down', 'change' => '6'),
                        array('rank' => '25', 'unit' => Unit::where('draw_id','=','1009')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '26', 'unit' => Unit::where('draw_id','=','879')->first(), 'type' => 'up', 'change' => '10'),
                        array('rank' => '27', 'unit' => Unit::where('draw_id','=','752')->first(), 'type' => 'equal', 'change' => ''),
                        array('rank' => '28', 'unit' => Unit::where('draw_id','=','817')->first(), 'type' => 'up', 'change' => '13'),
                        array('rank' => '29', 'unit' => Unit::where('draw_id','=','768')->first(), 'type' => 'down', 'change' => '16'),
                        array('rank' => '30', 'unit' => Unit::where('draw_id','=','678')->first(), 'type' => 'down', 'change' => '13'),
                        array('rank' => '31', 'unit' => Unit::where('draw_id','=','146')->first(), 'type' => 'down', 'change' => '6'),
                        array('rank' => '32', 'unit' => Unit::where('draw_id','=','811')->first(), 'type' => 'down', 'change' => '10'),
                        array('rank' => '33', 'unit' => Unit::where('draw_id','=','766')->first(), 'type' => 'down', 'change' => '14'),
                        array('rank' => '34', 'unit' => Unit::where('draw_id','=','764')->first(), 'type' => 'down', 'change' => '8'),
                        array('rank' => '35', 'unit' => Unit::where('draw_id','=','899')->first(), 'type' => 'down', 'change' => '7'),
                        array('rank' => '36', 'unit' => Unit::where('draw_id','=','482')->first(), 'type' => 'down', 'change' => '5'),
                        array('rank' => '37', 'unit' => Unit::where('draw_id','=','355')->first(), 'type' => 'up', 'change' => '2'),
                        array('rank' => '38', 'unit' => Unit::where('draw_id','=','770')->first(), 'type' => 'down', 'change' => '9'),
                        array('rank' => '39', 'unit' => Unit::where('draw_id','=','343')->first(), 'type' => 'up', 'change' => '5'),
                        array('rank' => '40', 'unit' => Unit::where('draw_id','=','1023')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '41', 'unit' => Unit::where('draw_id','=','336')->first(), 'type' => 'up', 'change' => '6'),
                        array('rank' => '42', 'unit' => Unit::where('draw_id','=','819')->first(), 'type' => 'equal', 'change' => ''),
                        array('rank' => '43', 'unit' => Unit::where('draw_id','=','772')->first(), 'type' => 'down', 'change' => '13'),
                        array('rank' => '44', 'unit' => Unit::where('draw_id','=','1007')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '45', 'unit' => Unit::where('draw_id','=','351')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '46', 'unit' => Unit::where('draw_id','=','648')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '47', 'unit' => Unit::where('draw_id','=','636')->first(), 'type' => 'down', 'change' => '14'),
                        array('rank' => '48', 'unit' => Unit::where('draw_id','=','347')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '49', 'unit' => Unit::where('draw_id','=','927')->first(), 'type' => 'down', 'change' => '33'),
                        array('rank' => '50', 'unit' => Unit::where('draw_id','=','864')->first(), 'type' => 'down', 'change' => '10'),
                        array('rank' => '51', 'unit' => Unit::where('draw_id','=','274')->first(), 'type' => 'down', 'change' => '13'),
                        array('rank' => '52', 'unit' => Unit::where('draw_id','=','638')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '53', 'unit' => Unit::where('draw_id','=','264')->first(), 'type' => 'down', 'change' => '5'),
                        array('rank' => '54', 'unit' => Unit::where('draw_id','=','262')->first(), 'type' => 'down', 'change' => '20'),
                        array('rank' => '55', 'unit' => Unit::where('draw_id','=','286')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '56', 'unit' => Unit::where('draw_id','=','684')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '57', 'unit' => Unit::where('draw_id','=','428')->first(), 'type' => 'down', 'change' => '22'),
                        array('rank' => '58', 'unit' => Unit::where('draw_id','=','926')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '59', 'unit' => Unit::where('draw_id','=','152')->first(), 'type' => 'down', 'change' => '14'),
                        array('rank' => '60', 'unit' => Unit::where('draw_id','=','260')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '61', 'unit' => Unit::where('draw_id','=','349')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '62', 'unit' => Unit::where('draw_id','=','150')->first(), 'type' => 'down', 'change' => '19'),
                        array('rank' => '63', 'unit' => Unit::where('draw_id','=','632')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '64', 'unit' => Unit::where('draw_id','=','924')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '65', 'unit' => Unit::where('draw_id','=','333')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '66', 'unit' => Unit::where('draw_id','=','815')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '67', 'unit' => Unit::where('draw_id','=','973')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '68', 'unit' => Unit::where('draw_id','=','154')->first(), 'type' => 'down', 'change' => '18'),
                        array('rank' => '69', 'unit' => Unit::where('draw_id','=','916')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '70', 'unit' => Unit::where('draw_id','=','893')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '71', 'unit' => Unit::where('draw_id','=','625')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '72', 'unit' => Unit::where('draw_id','=','813')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '73', 'unit' => Unit::where('draw_id','=','881')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '74', 'unit' => Unit::where('draw_id','=','379')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '75', 'unit' => Unit::where('draw_id','=','272')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '76', 'unit' => Unit::where('draw_id','=','426')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '77', 'unit' => Unit::where('draw_id','=','686')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '78', 'unit' => Unit::where('draw_id','=','634')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '79', 'unit' => Unit::where('draw_id','=','630')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '80', 'unit' => Unit::where('draw_id','=','541')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '81', 'unit' => Unit::where('draw_id','=','375')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '82', 'unit' => Unit::where('draw_id','=','381')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '83', 'unit' => Unit::where('draw_id','=','496')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '84', 'unit' => Unit::where('draw_id','=','434')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '85', 'unit' => Unit::where('draw_id','=','156')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '86', 'unit' => Unit::where('draw_id','=','549')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '87', 'unit' => Unit::where('draw_id','=','628')->first(), 'type' => 'down', 'change' => '38'),
                        array('rank' => '88', 'unit' => Unit::where('draw_id','=','688')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '89', 'unit' => Unit::where('draw_id','=','877')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '90', 'unit' => Unit::where('draw_id','=','967')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '91', 'unit' => Unit::where('draw_id','=','534')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '92', 'unit' => Unit::where('draw_id','=','280')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '93', 'unit' => Unit::where('draw_id','=','885')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '94', 'unit' => Unit::where('draw_id','=','640')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '95', 'unit' => Unit::where('draw_id','=','918')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '96', 'unit' => Unit::where('draw_id','=','478')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '97', 'unit' => Unit::where('draw_id','=','337')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '98', 'unit' => Unit::where('draw_id','=','338')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '99', 'unit' => Unit::where('draw_id','=','1019')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '100', 'unit' => Unit::where('draw_id','=','430')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '101', 'unit' => Unit::where('draw_id','=','543')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '102', 'unit' => Unit::where('draw_id','=','353')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '103', 'unit' => Unit::where('draw_id','=','593')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '104', 'unit' => Unit::where('draw_id','=','258')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '105', 'unit' => Unit::where('draw_id','=','809')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '106', 'unit' => Unit::where('draw_id','=','959')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '107', 'unit' => Unit::where('draw_id','=','971')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '108', 'unit' => Unit::where('draw_id','=','680')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '109', 'unit' => Unit::where('draw_id','=','587')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '110', 'unit' => Unit::where('draw_id','=','977')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '111', 'unit' => Unit::where('draw_id','=','334')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '112', 'unit' => Unit::where('draw_id','=','591')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '113', 'unit' => Unit::where('draw_id','=','598')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '114', 'unit' => Unit::where('draw_id','=','292')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '115', 'unit' => Unit::where('draw_id','=','975')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '116', 'unit' => Unit::where('draw_id','=','883')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '117', 'unit' => Unit::where('draw_id','=','220')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '118', 'unit' => Unit::where('draw_id','=','424')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '119', 'unit' => Unit::where('draw_id','=','504')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '120', 'unit' => Unit::where('draw_id','=','335')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '121', 'unit' => Unit::where('draw_id','=','276')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '122', 'unit' => Unit::where('draw_id','=','545')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '123', 'unit' => Unit::where('draw_id','=','887')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '124', 'unit' => Unit::where('draw_id','=','284')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '125', 'unit' => Unit::where('draw_id','=','432')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '126', 'unit' => Unit::where('draw_id','=','268')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '127', 'unit' => Unit::where('draw_id','=','1025')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '128', 'unit' => Unit::where('draw_id','=','831')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '129', 'unit' => Unit::where('draw_id','=','339')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '130', 'unit' => Unit::where('draw_id','=','345')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '131', 'unit' => Unit::where('draw_id','=','532')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '132', 'unit' => Unit::where('draw_id','=','547')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '133', 'unit' => Unit::where('draw_id','=','823')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '134', 'unit' => Unit::where('draw_id','=','444')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '135', 'unit' => Unit::where('draw_id','=','706')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '136', 'unit' => Unit::where('draw_id','=','476')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '137', 'unit' => Unit::where('draw_id','=','922')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '138', 'unit' => Unit::where('draw_id','=','979')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '139', 'unit' => Unit::where('draw_id','=','1021')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '140', 'unit' => Unit::where('draw_id','=','714')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '141', 'unit' => Unit::where('draw_id','=','401')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '142', 'unit' => Unit::where('draw_id','=','955')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '143', 'unit' => Unit::where('draw_id','=','266')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '144', 'unit' => Unit::where('draw_id','=','449')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '145', 'unit' => Unit::where('draw_id','=','294')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '146', 'unit' => Unit::where('draw_id','=','484')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '147', 'unit' => Unit::where('draw_id','=','969')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '148', 'unit' => Unit::where('draw_id','=','539')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '149', 'unit' => Unit::where('draw_id','=','1015')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '150', 'unit' => Unit::where('draw_id','=','448')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '151', 'unit' => Unit::where('draw_id','=','270')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '152', 'unit' => Unit::where('draw_id','=','716')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '153', 'unit' => Unit::where('draw_id','=','698')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '154', 'unit' => Unit::where('draw_id','=','957')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '155', 'unit' => Unit::where('draw_id','=','756')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '156', 'unit' => Unit::where('draw_id','=','583')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '157', 'unit' => Unit::where('draw_id','=','395')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '158', 'unit' => Unit::where('draw_id','=','961')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '159', 'unit' => Unit::where('draw_id','=','397')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '160', 'unit' => Unit::where('draw_id','=','282')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '161', 'unit' => Unit::where('draw_id','=','500')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '162', 'unit' => Unit::where('draw_id','=','606')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '163', 'unit' => Unit::where('draw_id','=','480')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '164', 'unit' => Unit::where('draw_id','=','492')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '165', 'unit' => Unit::where('draw_id','=','557')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '166', 'unit' => Unit::where('draw_id','=','136')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '167', 'unit' => Unit::where('draw_id','=','782')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '168', 'unit' => Unit::where('draw_id','=','827')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '169', 'unit' => Unit::where('draw_id','=','445')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '170', 'unit' => Unit::where('draw_id','=','710')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '171', 'unit' => Unit::where('draw_id','=','331')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '172', 'unit' => Unit::where('draw_id','=','696')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '173', 'unit' => Unit::where('draw_id','=','1017')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '174', 'unit' => Unit::where('draw_id','=','589')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '175', 'unit' => Unit::where('draw_id','=','134')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '176', 'unit' => Unit::where('draw_id','=','221')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '177', 'unit' => Unit::where('draw_id','=','602')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '178', 'unit' => Unit::where('draw_id','=','780')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '179', 'unit' => Unit::where('draw_id','=','585')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '180', 'unit' => Unit::where('draw_id','=','951')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '181', 'unit' => Unit::where('draw_id','=','790')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '182', 'unit' => Unit::where('draw_id','=','802')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '183', 'unit' => Unit::where('draw_id','=','124')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '184', 'unit' => Unit::where('draw_id','=','442')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '185', 'unit' => Unit::where('draw_id','=','288')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '186', 'unit' => Unit::where('draw_id','=','447')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '187', 'unit' => Unit::where('draw_id','=','393')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '188', 'unit' => Unit::where('draw_id','=','754')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '189', 'unit' => Unit::where('draw_id','=','391')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '190', 'unit' => Unit::where('draw_id','=','446')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '191', 'unit' => Unit::where('draw_id','=','502')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '192', 'unit' => Unit::where('draw_id','=','290')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '193', 'unit' => Unit::where('draw_id','=','399')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '194', 'unit' => Unit::where('draw_id','=','403')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '195', 'unit' => Unit::where('draw_id','=','953')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '196', 'unit' => Unit::where('draw_id','=','868')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '197', 'unit' => Unit::where('draw_id','=','829')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '198', 'unit' => Unit::where('draw_id','=','920')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '199', 'unit' => Unit::where('draw_id','=','223')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '200', 'unit' => Unit::where('draw_id','=','385')->first(), 'type' => 'new', 'change' => ''),
                    );
                    break;
                case 7:
                    $data = array(
                        array('rank' => '1', 'unit' => Unit::where('draw_id','=','1011')->first(), 'type' => 'equal', 'change' => ''),
                        array('rank' => '2', 'unit' => Unit::where('draw_id','=','866')->first(), 'type' => 'equal', 'change' => ''),
                        array('rank' => '3', 'unit' => Unit::where('draw_id','=','650')->first(), 'type' => 'equal', 'change' => ''),
                        array('rank' => '4', 'unit' => Unit::where('draw_id','=','965')->first(), 'type' => 'up', 'change' => '1'),
                        array('rank' => '5', 'unit' => Unit::where('draw_id','=','1064')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '6', 'unit' => Unit::where('draw_id','=','1205')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '7', 'unit' => Unit::where('draw_id','=','296')->first(), 'type' => 'down', 'change' => '3'),
                        array('rank' => '8', 'unit' => Unit::where('draw_id','=','896')->first(), 'type' => 'up', 'change' => '5'),
                        array('rank' => '9', 'unit' => Unit::where('draw_id','=','1266')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '10', 'unit' => Unit::where('draw_id','=','1123')->first(), 'type' => 'up', 'change' => '35'),
                        array('rank' => '11', 'unit' => Unit::where('draw_id','=','898')->first(), 'type' => 'down', 'change' => '2'),
                        array('rank' => '12', 'unit' => Unit::where('draw_id','=','1012')->first(), 'type' => 'down', 'change' => '6'),
                        array('rank' => '13', 'unit' => Unit::where('draw_id','=','897')->first(), 'type' => 'down', 'change' => '3'),
                        array('rank' => '14', 'unit' => Unit::where('draw_id','=','895')->first(), 'type' => 'up', 'change' => '1'),
                        array('rank' => '15', 'unit' => Unit::where('draw_id','=','1268')->first(), 'type' => 'up', 'change' => '99'),
                        array('rank' => '16', 'unit' => Unit::where('draw_id','=','894')->first(), 'type' => 'up', 'change' => '2'),
                        array('rank' => '17', 'unit' => Unit::where('draw_id','=','1215')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '18', 'unit' => Unit::where('draw_id','=','963')->first(), 'type' => 'down', 'change' => '7'),
                        array('rank' => '19', 'unit' => Unit::where('draw_id','=','750')->first(), 'type' => 'down', 'change' => '11'),
                        array('rank' => '20', 'unit' => Unit::where('draw_id','=','778')->first(), 'type' => 'down', 'change' => '3'),
                        array('rank' => '21', 'unit' => Unit::where('draw_id','=','610')->first(), 'type' => 'down', 'change' => '14'),
                        array('rank' => '22', 'unit' => Unit::where('draw_id','=','1122')->first(), 'type' => 'up', 'change' => '39'),
                        array('rank' => '23', 'unit' => Unit::where('draw_id','=','682')->first(), 'type' => 'equal', 'change' => ''),
                        array('rank' => '24', 'unit' => Unit::where('draw_id','=','1112')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '25', 'unit' => Unit::where('draw_id','=','1148')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '26', 'unit' => Unit::where('draw_id','=','474')->first(), 'type' => 'down', 'change' => '26'),
                        array('rank' => '27', 'unit' => Unit::where('draw_id','=','1004')->first(), 'type' => 'down', 'change' => '13'),
                        array('rank' => '28', 'unit' => Unit::where('draw_id','=','1012')->first(), 'type' => 'down', 'change' => '22'),
                        array('rank' => '29', 'unit' => Unit::where('draw_id','=','148')->first(), 'type' => 'down', 'change' => '9'),
                        array('rank' => '30', 'unit' => Unit::where('draw_id','=','879')->first(), 'type' => 'down', 'change' => '4'),
                        array('rank' => '31', 'unit' => Unit::where('draw_id','=','559')->first(), 'type' => 'down', 'change' => '12'),
                        array('rank' => '32', 'unit' => Unit::where('draw_id','=','1214')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '33', 'unit' => Unit::where('draw_id','=','1267')->first(), 'type' => 'up', 'change' => '18'),
                        array('rank' => '34', 'unit' => Unit::where('draw_id','=','811')->first(), 'type' => 'down', 'change' => '2'),
                        array('rank' => '35', 'unit' => Unit::where('draw_id','=','1216')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '36', 'unit' => Unit::where('draw_id','=','1115')->first(), 'type' => 'up', 'change' => '39'),
                        array('rank' => '37', 'unit' => Unit::where('draw_id','=','762')->first(), 'type' => 'down', 'change' => '13'),
                        array('rank' => '38', 'unit' => Unit::where('draw_id','=','1201')->first(), 'type' => 'up', 'change' => '154'),
                        array('rank' => '39', 'unit' => Unit::where('draw_id','=','768')->first(), 'type' => 'down', 'change' => '10'),
                        array('rank' => '40', 'unit' => Unit::where('draw_id','=','1013')->first(), 'type' => 'down', 'change' => '28'),
                    );
                    break;
                case 8:
                    $data['all'] = array(
                        array('rank' => '1', 'unit' => Unit::where('draw_id','=','1011')->first(), 'type' => 'equal', 'change' => ''),
                        array('rank' => '2', 'unit' => Unit::where('draw_id','=','866')->first(), 'type' => 'equal', 'change' => ''),
                        array('rank' => '3', 'unit' => Unit::where('draw_id','=','1064')->first(), 'type' => 'up', 'change' => '2'),
                        array('rank' => '4', 'unit' => Unit::where('draw_id','=','965')->first(), 'type' => 'equal', 'change' => ''),
                        array('rank' => '5', 'unit' => Unit::where('draw_id','=','1277')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '6', 'unit' => Unit::where('draw_id','=','650')->first(), 'type' => 'down', 'change' => '3'),
                        array('rank' => '7', 'unit' => Unit::where('draw_id','=','1205')->first(), 'type' => 'down', 'change' => '1'),
                        array('rank' => '8', 'unit' => Unit::where('draw_id','=','1378')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '9', 'unit' => Unit::where('draw_id','=','1273')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '10', 'unit' => Unit::where('draw_id','=','1012')->first(), 'type' => 'up', 'change' => '18'),
                        array('rank' => '11', 'unit' => Unit::where('draw_id','=','296')->first(), 'type' => 'down', 'change' => '4'),
                        array('rank' => '12', 'unit' => Unit::where('draw_id','=','1382')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '13', 'unit' => Unit::where('draw_id','=','1123')->first(), 'type' => 'equal', 'change' => ''),
                        array('rank' => '14', 'unit' => Unit::where('draw_id','=','1266')->first(), 'type' => 'down', 'change' => '5'),
                        array('rank' => '15', 'unit' => Unit::where('draw_id','=','1325')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '16', 'unit' => Unit::where('draw_id','=','963')->first(), 'type' => 'up', 'change' => '2'),
                        array('rank' => '17', 'unit' => Unit::where('draw_id','=','1112')->first(), 'type' => 'up', 'change' => '7'),
                        array('rank' => '18', 'unit' => Unit::where('draw_id','=','750')->first(), 'type' => 'up', 'change' => '1'),
                        array('rank' => '19', 'unit' => Unit::where('draw_id','=','474')->first(), 'type' => 'up', 'change' => '7'),
                        array('rank' => '20', 'unit' => Unit::where('draw_id','=','1354')->first(), 'type' => 'new', 'change' => ''),
                    );
                    $data['fire'] = array(
                        array('rank' => '1', 'unit' => Unit::where('draw_id','=','1064')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '2', 'unit' => Unit::where('draw_id','=','1382')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '3', 'unit' => Unit::where('draw_id','=','474')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '4', 'unit' => Unit::where('draw_id','=','778')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '5', 'unit' => Unit::where('draw_id','=','1338')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '6', 'unit' => Unit::where('draw_id','=','1115')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '7', 'unit' => Unit::where('draw_id','=','894')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '8', 'unit' => Unit::where('draw_id','=','4')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '9', 'unit' => Unit::where('draw_id','=','146')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '10', 'unit' => Unit::where('draw_id','=','762')->first(), 'type' => '', 'change' => ''),
                    );
                    $data['water'] = array(
                        array('rank' => '1', 'unit' => Unit::where('draw_id','=','650')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '2', 'unit' => Unit::where('draw_id','=','1012')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '3', 'unit' => Unit::where('draw_id','=','1200')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '4', 'unit' => Unit::where('draw_id','=','1271')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '5', 'unit' => Unit::where('draw_id','=','148')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '6', 'unit' => Unit::where('draw_id','=','895')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '7', 'unit' => Unit::where('draw_id','=','879')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '8', 'unit' => Unit::where('draw_id','=','752')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '9', 'unit' => Unit::where('draw_id','=','1162')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '10', 'unit' => Unit::where('draw_id','=','8')->first(), 'type' => '', 'change' => ''),
                    );
                    $data['wind'] = array(
                        array('rank' => '1', 'unit' => Unit::where('draw_id','=','1325')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '2', 'unit' => Unit::where('draw_id','=','963')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '3', 'unit' => Unit::where('draw_id','=','1112')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '4', 'unit' => Unit::where('draw_id','=','559')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '5', 'unit' => Unit::where('draw_id','=','1122')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '6', 'unit' => Unit::where('draw_id','=','1339')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '7', 'unit' => Unit::where('draw_id','=','12')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '8', 'unit' => Unit::where('draw_id','=','896')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '9', 'unit' => Unit::where('draw_id','=','682')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '10', 'unit' => Unit::where('draw_id','=','766')->first(), 'type' => '', 'change' => ''),
                    );
                    $data['light'] = array(
                        array('rank' => '1', 'unit' => Unit::where('draw_id','=','1011')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '2', 'unit' => Unit::where('draw_id','=','296')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '3', 'unit' => Unit::where('draw_id','=','1123')->first(), 'type' => '', 'change' => '', 'end' => false),
                        array('rank' => '3', 'unit' => Unit::where('draw_id','=','1266')->first(), 'type' => '', 'change' => '', 'end' => true),
                        array('rank' => '5', 'unit' => Unit::where('draw_id','=','750')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '6', 'unit' => Unit::where('draw_id','=','897')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '7', 'unit' => Unit::where('draw_id','=','16')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '8', 'unit' => Unit::where('draw_id','=','290')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '9', 'unit' => Unit::where('draw_id','=','684')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '10', 'unit' => Unit::where('draw_id','=','768')->first(), 'type' => '', 'change' => ''),
                    );
                    $data['dark'] = array(
                        array('rank' => '1', 'unit' => Unit::where('draw_id','=','965')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '2', 'unit' => Unit::where('draw_id','=','1277')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '3', 'unit' => Unit::where('draw_id','=','1273')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '4', 'unit' => Unit::where('draw_id','=','1354')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '5', 'unit' => Unit::where('draw_id','=','610')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '6', 'unit' => Unit::where('draw_id','=','898')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '7', 'unit' => Unit::where('draw_id','=','20')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '8', 'unit' => Unit::where('draw_id','=','1268')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '9', 'unit' => Unit::where('draw_id','=','1270')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '10', 'unit' => Unit::where('draw_id','=','817')->first(), 'type' => '', 'change' => ''),
                    );
                    $data['none'] = array(
                        array('rank' => '1', 'unit' => Unit::where('draw_id','=','866')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '2', 'unit' => Unit::where('draw_id','=','1205')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '3', 'unit' => Unit::where('draw_id','=','1378')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '4', 'unit' => Unit::where('draw_id','=','1215')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '5', 'unit' => Unit::where('draw_id','=','1004')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '6', 'unit' => Unit::where('draw_id','=','1013')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '7', 'unit' => Unit::where('draw_id','=','436')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '8', 'unit' => Unit::where('draw_id','=','355')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '9', 'unit' => Unit::where('draw_id','=','1009')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '10', 'unit' => Unit::where('draw_id','=','218')->first(), 'type' => '', 'change' => ''),
                    );
                    break;
                case 9:
                    $data = array(
                        array('rank' => '5', 'unit' => Unit::where('draw_id','=','1011')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '7', 'unit' => Unit::where('draw_id','=','650')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '12', 'unit' => Unit::where('draw_id','=','898')->first(), 'type' => '', 'change' => ''),
                    );
                    break;
                case 10:
                    $data = array(
                        array('rank' => '1', 'unit' => Unit::where('draw_id','=','1502')->first(), 'type' => 'up', 'change' => '5'),
                        array('rank' => '2', 'unit' => Unit::where('draw_id','=','1011')->first(), 'type' => 'down', 'change' => '1'),
                        array('rank' => '3', 'unit' => Unit::where('draw_id','=','866')->first(), 'type' => 'down', 'change' => '1'),
                        array('rank' => '4', 'unit' => Unit::where('draw_id','=','1509')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '5', 'unit' => Unit::where('draw_id','=','1064')->first(), 'type' => 'down', 'change' => '2'),
                        array('rank' => '6', 'unit' => Unit::where('draw_id','=','965')->first(), 'type' => 'down', 'change' => '2'),
                        array('rank' => '7', 'unit' => Unit::where('draw_id','=','1445')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '8', 'unit' => Unit::where('draw_id','=','1277')->first(), 'type' => 'down', 'change' => '3'),
                        array('rank' => '9', 'unit' => Unit::where('draw_id','=','1479')->first(), 'type' => 'up', 'change' => '7'),
                        array('rank' => '10', 'unit' => Unit::where('draw_id','=','296')->first(), 'type' => 'up', 'change' => '1'),
                        array('rank' => '11', 'unit' => Unit::where('draw_id','=','1205')->first(), 'type' => 'down', 'change' => '4'),
                        array('rank' => '12', 'unit' => Unit::where('draw_id','=','1325')->first(), 'type' => 'up', 'change' => '3'),
                        array('rank' => '13', 'unit' => Unit::where('draw_id','=','1266')->first(), 'type' => 'up', 'change' => '1'),
                        array('rank' => '14', 'unit' => Unit::where('draw_id','=','1429')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '15', 'unit' => Unit::where('draw_id','=','1012')->first(), 'type' => 'down', 'change' => '5'),
                        array('rank' => '16', 'unit' => Unit::where('draw_id','=','898')->first(), 'type' => 'up', 'change' => '4'),
                        array('rank' => '17', 'unit' => Unit::where('draw_id','=','1382')->first(), 'type' => 'down', 'change' => '5'),
                        array('rank' => '18', 'unit' => Unit::where('draw_id','=','1273')->first(), 'type' => 'down', 'change' => '9'),
                        array('rank' => '19', 'unit' => Unit::where('draw_id','=','1478')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '20', 'unit' => Unit::where('draw_id','=','1481')->first(), 'type' => 'new', 'change' => ''),
                    );
                    break;
                case 11:
                    $data = array(
                        array('rank' => '1', 'unit' => Unit::where('draw_id','=','1821')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '2', 'unit' => Unit::where('draw_id','=','1011')->first(), 'type' => 'equal', 'change' => ''),
                        array('rank' => '3', 'unit' => Unit::where('draw_id','=','1502')->first(), 'type' => 'down', 'change' => '2'),
                        array('rank' => '4', 'unit' => Unit::where('draw_id','=','866')->first(), 'type' => 'down', 'change' => '1'),
                        array('rank' => '5', 'unit' => Unit::where('draw_id','=','1064')->first(), 'type' => 'equal', 'change' => ''),
                        array('rank' => '6', 'unit' => Unit::where('draw_id','=','1810')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '7', 'unit' => Unit::where('draw_id','=','965')->first(), 'type' => 'down', 'change' => '1'),
                        array('rank' => '8', 'unit' => Unit::where('draw_id','=','1277')->first(), 'type' => 'equal', 'change' => ''),
                        array('rank' => '9', 'unit' => Unit::where('draw_id','=','1445')->first(), 'type' => 'down', 'change' => '2'),
                        array('rank' => '10', 'unit' => Unit::where('draw_id','=','1509')->first(), 'type' => 'down', 'change' => '6'),
                        array('rank' => '11', 'unit' => Unit::where('draw_id','=','650')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '11', 'unit' => Unit::where('draw_id','=','1012')->first(), 'type' => 'up', 'change' => '4'),
                        array('rank' => '13', 'unit' => Unit::where('draw_id','=','1479')->first(), 'type' => 'down', 'change' => '4'),
                        array('rank' => '14', 'unit' => Unit::where('draw_id','=','1851')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '14', 'unit' => Unit::where('draw_id','=','1205')->first(), 'type' => 'down', 'change' => '3'),
                        array('rank' => '16', 'unit' => Unit::where('draw_id','=','1429')->first(), 'type' => 'down', 'change' => '2'),
                        array('rank' => '17', 'unit' => Unit::where('draw_id','=','1266')->first(), 'type' => 'down', 'change' => '4'),
                        array('rank' => '18', 'unit' => Unit::where('draw_id','=','296')->first(), 'type' => 'down', 'change' => '8'),
                        array('rank' => '18', 'unit' => Unit::where('draw_id','=','1325')->first(), 'type' => 'down', 'change' => '6'),
                        array('rank' => '20', 'unit' => Unit::where('draw_id','=','474')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '21', 'unit' => Unit::where('draw_id','=','750')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '22', 'unit' => Unit::where('draw_id','=','1788')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '23', 'unit' => Unit::where('draw_id','=','895')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '24', 'unit' => Unit::where('draw_id','=','1809')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '25', 'unit' => Unit::where('draw_id','=','1481')->first(), 'type' => 'down', 'change' => '5'),
                        array('rank' => '26', 'unit' => Unit::where('draw_id','=','1818')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '27', 'unit' => Unit::where('draw_id','=','1478')->first(), 'type' => 'down', 'change' => '8'),
                        array('rank' => '28', 'unit' => Unit::where('draw_id','=','1273')->first(), 'type' => 'down', 'change' => '10'),
                        array('rank' => '29', 'unit' => Unit::where('draw_id','=','1382')->first(), 'type' => 'down', 'change' => '12'),
                        array('rank' => '30', 'unit' => Unit::where('draw_id','=','1013')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '31', 'unit' => Unit::where('draw_id','=','1123')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '32', 'unit' => Unit::where('draw_id','=','778')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '33', 'unit' => Unit::where('draw_id','=','896')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '34', 'unit' => Unit::where('draw_id','=','1454')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '34', 'unit' => Unit::where('draw_id','=','1271')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '36', 'unit' => Unit::where('draw_id','=','898')->first(), 'type' => 'down', 'change' => '20'),
                        array('rank' => '37', 'unit' => Unit::where('draw_id','=','1783')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '38', 'unit' => Unit::where('draw_id','=','8')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '39', 'unit' => Unit::where('draw_id','=','894')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '40', 'unit' => Unit::where('draw_id','=','20')->first(), 'type' => 'new', 'change' => ''),
                    );
                    break;
                case 12:
                    $data = array(
                        array('rank' => '1', 'unit' => Unit::where('draw_id','=','1011')->first(), 'type' => 'up', 'change' => '1'),
                        array('rank' => '2', 'unit' => Unit::where('draw_id','=','1821')->first(), 'type' => 'down', 'change' => '1'),
                        array('rank' => '3', 'unit' => Unit::where('draw_id','=','866')->first(), 'type' => 'up', 'change' => '1'),
                        array('rank' => '4', 'unit' => Unit::where('draw_id','=','1502')->first(), 'type' => 'down', 'change' => '1'),
                        array('rank' => '5', 'unit' => Unit::where('draw_id','=','1925')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '6', 'unit' => Unit::where('draw_id','=','965')->first(), 'type' => 'up', 'change' => '1'),
                        array('rank' => '7', 'unit' => Unit::where('draw_id','=','1064')->first(), 'type' => 'down', 'change' => '2'),
                        array('rank' => '8', 'unit' => Unit::where('draw_id','=','1445')->first(), 'type' => 'up', 'change' => '1'),
                        array('rank' => '9', 'unit' => Unit::where('draw_id','=','1277')->first(), 'type' => 'down', 'change' => '1'),
                        array('rank' => '10', 'unit' => Unit::where('draw_id','=','1810')->first(), 'type' => 'down', 'change' => '4'),
                        array('rank' => '11', 'unit' => Unit::where('draw_id','=','1509')->first(), 'type' => 'down', 'change' => '1'),
                        array('rank' => '12', 'unit' => Unit::where('draw_id','=','1966')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '13', 'unit' => Unit::where('draw_id','=','650')->first(), 'type' => 'down', 'change' => '2'),
                        array('rank' => '14', 'unit' => Unit::where('draw_id','=','1479')->first(), 'type' => 'down', 'change' => '1'),
                        array('rank' => '15', 'unit' => Unit::where('draw_id','=','1205')->first(), 'type' => 'down', 'change' => '1'),
                        array('rank' => '16', 'unit' => Unit::where('draw_id','=','1012')->first(), 'type' => 'down', 'change' => '5'),
                        array('rank' => '17', 'unit' => Unit::where('draw_id','=','1429')->first(), 'type' => 'down', 'change' => '1'),
                        array('rank' => '18', 'unit' => Unit::where('draw_id','=','1266')->first(), 'type' => 'down', 'change' => '1'),
                        array('rank' => '19', 'unit' => Unit::where('draw_id','=','1325')->first(), 'type' => 'down', 'change' => '1'),
                        array('rank' => '20', 'unit' => Unit::where('draw_id','=','296')->first(), 'type' => 'down', 'change' => '2'),
                        array('rank' => '21', 'unit' => Unit::where('draw_id','=','474')->first(), 'type' => 'down', 'change' => '1'),
                        array('rank' => '22', 'unit' => Unit::where('draw_id','=','750')->first(), 'type' => 'down', 'change' => '1'),
                        array('rank' => '23', 'unit' => Unit::where('draw_id','=','1382')->first(), 'type' => 'up', 'change' => '6'),
                        array('rank' => '24', 'unit' => Unit::where('draw_id','=','1809')->first(), 'type' => 'equal', 'change' => ''),
                        array('rank' => '25', 'unit' => Unit::where('draw_id','=','894')->first(), 'type' => 'up', 'change' => '14'),
                        array('rank' => '26', 'unit' => Unit::where('draw_id','=','1481')->first(), 'type' => 'down', 'change' => '1'),
                        array('rank' => '27', 'unit' => Unit::where('draw_id','=','1013')->first(), 'type' => 'up', 'change' => '3'),
                        array('rank' => '28', 'unit' => Unit::where('draw_id','=','1788')->first(), 'type' => 'down', 'change' => '6'),
                        array('rank' => '29', 'unit' => Unit::where('draw_id','=','1273')->first(), 'type' => 'down', 'change' => '1'),
                        array('rank' => '30', 'unit' => Unit::where('draw_id','=','895')->first(), 'type' => 'down', 'change' => '7'),
                        array('rank' => '31', 'unit' => Unit::where('draw_id','=','1478')->first(), 'type' => 'down', 'change' => '4'),
                        array('rank' => '32', 'unit' => Unit::where('draw_id','=','1965')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '33', 'unit' => Unit::where('draw_id','=','896')->first(), 'type' => 'equal', 'change' => ''),
                        array('rank' => '33', 'unit' => Unit::where('draw_id','=','1851')->first(), 'type' => 'down', 'change' => '19'),
                        array('rank' => '35', 'unit' => Unit::where('draw_id','=','1967')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '36', 'unit' => Unit::where('draw_id','=','1951')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '37', 'unit' => Unit::where('draw_id','=','1454')->first(), 'type' => 'down', 'change' => '3'),
                        array('rank' => '38', 'unit' => Unit::where('draw_id','=','778')->first(), 'type' => 'down', 'change' => '6'),
                        array('rank' => '39', 'unit' => Unit::where('draw_id','=','1378')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '40', 'unit' => Unit::where('draw_id','=','1123')->first(), 'type' => 'down', 'change' => '9'),
                        array('rank' => '40', 'unit' => Unit::where('draw_id','=','1818')->first(), 'type' => 'down', 'change' => '14'),
                        array('rank' => '42', 'unit' => Unit::where('draw_id','=','8')->first(), 'type' => 'down', 'change' => '4'),
                        array('rank' => '43', 'unit' => Unit::where('draw_id','=','1112')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '44', 'unit' => Unit::where('draw_id','=','1947')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '45', 'unit' => Unit::where('draw_id','=','1969')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '46', 'unit' => Unit::where('draw_id','=','1781')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '47', 'unit' => Unit::where('draw_id','=','1422')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '48', 'unit' => Unit::where('draw_id','=','1010')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '48', 'unit' => Unit::where('draw_id','=','1004')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '50', 'unit' => Unit::where('draw_id','=','762')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '50', 'unit' => Unit::where('draw_id','=','1883')->first(), 'type' => 'new', 'change' => ''),
                    );
                    break;
                case 13:
                    $data['fav'] = array(
                        array('rank' => '1', 'unit' => Unit::where('draw_id','=','2347')->first(), 'type' => 'equal', 'change' => ''),
                        array('rank' => '2', 'unit' => Unit::where('draw_id','=','1502')->first(), 'type' => 'up', 'change' => '2'),
                        array('rank' => '3', 'unit' => Unit::where('draw_id','=','2182')->first(), 'type' => 'equal', 'change' => ''),
                        array('rank' => '4', 'unit' => Unit::where('draw_id','=','1821')->first(), 'type' => 'down', 'change' => '2'),
                        array('rank' => '5', 'unit' => Unit::where('draw_id','=','1064')->first(), 'type' => 'up', 'change' => '2'),
                        array('rank' => '6', 'unit' => Unit::where('draw_id','=','2195')->first(), 'type' => 'equal', 'change' => ''),
                        array('rank' => '7', 'unit' => Unit::where('draw_id','=','1277')->first(), 'type' => 'up', 'change' => '2'),
                        array('rank' => '8', 'unit' => Unit::where('draw_id','=','2343')->first(), 'type' => 'new', 'change' => ''),
                        array('rank' => '9', 'unit' => Unit::where('draw_id','=','1925')->first(), 'type' => 'down', 'change' => '4'),
                        array('rank' => '10', 'unit' => Unit::where('draw_id','=','1445')->first(), 'type' => 'down', 'change' => '2'),
                        array('rank' => '11', 'unit' => Unit::where('draw_id','=','1509')->first(), 'type' => 'equal', 'change' => ''),
                        array('rank' => '12', 'unit' => Unit::where('draw_id','=','1809')->first(), 'type' => 'down', 'change' => '2'),
                        array('rank' => '15', 'unit' => Unit::where('draw_id','=','2359')->first(), 'type' => 'up', 'change' => '7'),
                        array('rank' => '16', 'unit' => Unit::where('draw_id','=','2183')->first(), 'type' => 'up', 'change' => '2'),
                        array('rank' => '17', 'unit' => Unit::where('draw_id','=','898')->first(), 'type' => 'down', 'change' => '5'),
                        array('rank' => '18', 'unit' => Unit::where('draw_id','=','2237')->first(), 'type' => 'up', 'change' => '1'),
                        array('rank' => '21', 'unit' => Unit::where('draw_id','=','1012')->first(), 'type' => 'down', 'change' => '5'),
                    );
                    $data['tsundere'] = array(
                        array('rank' => '1', 'unit' => Unit::where('draw_id','=','2347')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '2', 'unit' => Unit::where('draw_id','=','898')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '3', 'unit' => Unit::where('draw_id','=','1277')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '4', 'unit' => Unit::where('draw_id','=','1925')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '5', 'unit' => Unit::where('draw_id','=','866')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '8', 'unit' => Unit::where('draw_id','=','1064')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '13', 'unit' => Unit::where('draw_id','=','1012')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '15', 'unit' => Unit::where('draw_id','=','1478')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '16', 'unit' => Unit::where('draw_id','=','1429')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '32', 'unit' => Unit::where('draw_id','=','382')->first(), 'type' => '', 'change' => ''),
                    );
                    $data['wife'] = array(
                        array('rank' => '1', 'unit' => Unit::where('draw_id','=','1502')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '2', 'unit' => Unit::where('draw_id','=','897')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '3', 'unit' => Unit::where('draw_id','=','2347')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '4', 'unit' => Unit::where('draw_id','=','1925')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '5', 'unit' => Unit::where('draw_id','=','1115')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '19', 'unit' => Unit::where('draw_id','=','894')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '20', 'unit' => Unit::where('draw_id','=','1339')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '21', 'unit' => Unit::where('draw_id','=','895')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '38', 'unit' => Unit::where('draw_id','=','899')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '39', 'unit' => Unit::where('draw_id','=','382')->first(), 'type' => '', 'change' => ''),
                    );
                    $data['cook'] = array(
                        array('rank' => '1', 'unit' => Unit::where('draw_id','=','2347')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '2', 'unit' => Unit::where('draw_id','=','1162')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '3', 'unit' => Unit::where('draw_id','=','1112')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '4', 'unit' => Unit::where('draw_id','=','1502')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '5', 'unit' => Unit::where('draw_id','=','1479')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '7', 'unit' => Unit::where('draw_id','=','897')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '11', 'unit' => Unit::where('draw_id','=','2417')->first(), 'type' => '', 'change' => ''),
                        array('rank' => '13', 'unit' => Unit::where('draw_id','=','382')->first(), 'type' => '', 'change' => ''),
                    );
                    break;
                default:
                    break;
            }
            // cache
            Cache::forever($key, $data);
        }
        return $data;
    }
}
?>