<?php
/*
 * Copyright 2022 LABOR.digital
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Last modified: 2022.04.12 at 14:54
 */

declare(strict_types=1);

namespace LaborDigital\T3sai\Core\StopWords\Implementation;


class GermanStopWords extends AbstractStopWordList
{
    protected const STOP_WORDS
        = [
            'ab',
            'aber',
            'ach',
            'acht',
            'achte',
            'achten',
            'achter',
            'achtes',
            'ag',
            'alle',
            'allein',
            'allem',
            'allen',
            'aller',
            'allerdings',
            'alles',
            'allgemeinen',
            'als',
            'also',
            'am',
            'an',
            'ander',
            'andere',
            'anderem',
            'anderen',
            'anderer',
            'anderes',
            'anderm',
            'andern',
            'anders',
            'au',
            'auch',
            'auf',
            'aus',
            'ausser',
            'außer',
            'ausserdem',
            'außerdem',
            'bald',
            'bei',
            'beide',
            'beiden',
            'beim',
            'beispiel',
            'bekannt',
            'bereits',
            'besonders',
            'besser',
            'besten',
            'bin',
            'bis',
            'bisher',
            'bist',
            'd.h',
            'da',
            'dabei',
            'dadurch',
            'dafuer',
            'dafür',
            'dagegen',
            'daher',
            'dahin',
            'dahinter',
            'damals',
            'damit',
            'danach',
            'daneben',
            'dank',
            'dann',
            'daran',
            'darauf',
            'daraus',
            'darf',
            'darfst',
            'darin',
            'darum',
            'darunter',
            'darueber',
            'darüber',
            'das',
            'dasein',
            'daselbst',
            'dass',
            'dasselbe',
            'davon',
            'davor',
            'dazu',
            'dazwischen',
            'dein',
            'deine',
            'deinem',
            'deinen',
            'deiner',
            'deines',
            'dem',
            'dementsprechend',
            'demgegenueber',
            'demgegenüber',
            'demgemaess',
            'demgemaeß',
            'demgemäss',
            'demgemäß',
            'demselben',
            'demzufolge',
            'den',
            'denen',
            'denn',
            'denselben',
            'der',
            'deren',
            'derer',
            'derjenige',
            'derjenigen',
            'dermassen',
            'dermaßen',
            'derselbe',
            'derselben',
            'des',
            'deshalb',
            'desselben',
            'dessen',
            'deswegen',
            'dich',
            'die',
            'diejenige',
            'diejenigen',
            'dies',
            'diese',
            'dieselbe',
            'dieselben',
            'diesem',
            'diesen',
            'dieser',
            'dieses',
            'dir',
            'doch',
            'dort',
            'drei',
            'drin',
            'dritte',
            'dritten',
            'dritter',
            'drittes',
            'du',
            'durch',
            'durchaus',
            'durfte',
            'durften',
            'duerfen',
            'dürfen',
            'duerft',
            'dürft',
            'eben',
            'ebenso',
            'ehrlich',
            'ei',
            'ei,',
            'eigen',
            'eigene',
            'eigenen',
            'eigener',
            'eigenes',
            'ein',
            'einander',
            'eine',
            'einem',
            'einen',
            'einer',
            'eines',
            'einig',
            'einige',
            'einigem',
            'einigen',
            'einiger',
            'einiges',
            'einmal',
            'eins',
            'elf',
            'en',
            'ende',
            'endlich',
            'entweder',
            'er',
            'ernst',
            'erst',
            'erste',
            'ersten',
            'erster',
            'erstes',
            'es',
            'etwa',
            'etwas',
            'euch',
            'euer',
            'eure',
            'eurem',
            'euren',
            'eurer',
            'eures',
            'frueher',
            'früher',
            'fuenf',
            'fünf',
            'fuenfte',
            'fünfte',
            'fuenften',
            'fünften',
            'fuenfter',
            'fünfter',
            'fuenftes',
            'fünftes',
            'fuer',
            'für',
            'gab',
            'ganz',
            'ganze',
            'ganzen',
            'ganzer',
            'ganzes',
            'gar',
            'gedurft',
            'gegen',
            'gegenueber',
            'gegenüber',
            'gehabt',
            'gehen',
            'geht',
            'gekannt',
            'gekonnt',
            'gemacht',
            'gemocht',
            'gemusst',
            'genug',
            'gerade',
            'gern',
            'gesagt',
            'geschweige',
            'gewesen',
            'gewollt',
            'geworden',
            'gibt',
            'ging',
            'gleich',
            'gott',
            'gross',
            'groß',
            'grosse',
            'große',
            'grossen',
            'großen',
            'grosser',
            'großer',
            'grosses',
            'großes',
            'gross',
            'gut',
            'gute',
            'guter',
            'gutes',
            'hab',
            'habe',
            'haben',
            'habt',
            'hast',
            'hat',
            'hatte',
            'hatten',
            'hattest',
            'hattet',
            'heisst',
            'her',
            'heute',
            'hier',
            'hin',
            'hinter',
            'hoch',
            'haette',
            'hätte',
            'haetten',
            'hätten',
            'ich',
            'ihm',
            'ihn',
            'ihnen',
            'ihr',
            'ihre',
            'ihrem',
            'ihren',
            'ihrer',
            'ihres',
            'im',
            'immer',
            'in',
            'indem',
            'infolgedessen',
            'ins',
            'irgend',
            'ist',
            'ja',
            'jahr',
            'jahre',
            'jahren',
            'je',
            'jede',
            'jedem',
            'jeden',
            'jeder',
            'jedermann',
            'jedermanns',
            'jedes',
            'jedoch',
            'jemand',
            'jemandem',
            'jemanden',
            'jene',
            'jenem',
            'jenen',
            'jener',
            'jenes',
            'jetzt',
            'kam',
            'kann',
            'kannst',
            'kaum',
            'kein',
            'keine',
            'keinem',
            'keinen',
            'keiner',
            'keines',
            'kleine',
            'kleinen',
            'kleiner',
            'kleines',
            'kommen',
            'kommt',
            'konnte',
            'konnten',
            'kurz',
            'koennen',
            'können',
            'koennt',
            'könnt',
            'koennte',
            'könnte',
            'lang',
            'lange',
            'leicht',
            'leide',
            'lieber',
            'los',
            'machen',
            'macht',
            'machte',
            'mag',
            'magst',
            'mahn',
            'man',
            'manche',
            'manchem',
            'manchen',
            'mancher',
            'manches',
            'mann',
            'mehr',
            'mein',
            'meine',
            'meinem',
            'meinen',
            'meiner',
            'meines',
            'mensch',
            'menschen',
            'mich',
            'mir',
            'mit',
            'mittel',
            'mochte',
            'mochten',
            'morgen',
            'muss',
            'musst',
            'musste',
            'mussten',
            'muss',
            'musst',
            'moechte',
            'möchte',
            'moegen',
            'mögen',
            'moeglich',
            'möglich',
            'moegt',
            'mögt',
            'muessen',
            'müssen',
            'muesst',
            'müsst',
            'na',
            'nach',
            'nachdem',
            'nahm',
            'natuerlich',
            'natürlich',
            'neben',
            'nein',
            'neue',
            'neuen',
            'neun',
            'neunte',
            'neunten',
            'neunter',
            'neuntes',
            'nicht',
            'nichts',
            'nie',
            'niemand',
            'niemandem',
            'niemanden',
            'noch',
            'nun',
            'nur',
            'ob',
            'oben',
            'oder',
            'offen',
            'oft',
            'ohne',
            'ordnung',
            'recht',
            'rechte',
            'rechten',
            'rechter',
            'rechtes',
            'richtig',
            'rund',
            'sa',
            'sache',
            'sagt',
            'sagte',
            'sah',
            'satt',
            'schlecht',
            'schluss',
            'schon',
            'sechs',
            'sechste',
            'sechsten',
            'sechster',
            'sechstes',
            'sehr',
            'sei',
            'seid',
            'seien',
            'sein',
            'seine',
            'seinem',
            'seinen',
            'seiner',
            'seines',
            'seit',
            'seitdem',
            'selbst',
            'sich',
            'sie',
            'sieben',
            'siebente',
            'siebenten',
            'siebenter',
            'siebentes',
            'sind',
            'so',
            'solang',
            'solche',
            'solchem',
            'solchen',
            'solcher',
            'solches',
            'soll',
            'sollen',
            'sollst',
            'sollt',
            'sollte',
            'sollten',
            'sondern',
            'sonst',
            'soweit',
            'sowie',
            'spaeter',
            'später',
            'statt',
            'tag',
            'tage',
            'tagen',
            'tat',
            'teil',
            'tel',
            'tritt',
            'trotzdem',
            'tun',
            'uhr',
            'um',
            'und',
            'uns',
            'unse',
            'unsem',
            'unsen',
            'unser',
            'unsere',
            'unserer',
            'unses',
            'unter',
            'vergangenen',
            'viel',
            'viele',
            'vielem',
            'vielen',
            'vielleicht',
            'vier',
            'vierte',
            'vierten',
            'vierter',
            'viertes',
            'vom',
            'von',
            'vor',
            'wann',
            'war',
            'waren',
            'warst',
            'wart',
            'warum',
            'was',
            'weg',
            'wegen',
            'weil',
            'weit',
            'weiter',
            'weitere',
            'weiteren',
            'weiteres',
            'welche',
            'welchem',
            'welchen',
            'welcher',
            'welches',
            'wem',
            'wen',
            'wenig',
            'wenige',
            'weniger',
            'weniges',
            'wenigstens',
            'wenn',
            'wer',
            'werde',
            'werden',
            'werdet',
            'weshalb',
            'wessen',
            'wie',
            'wieder',
            'wieso',
            'will',
            'willst',
            'wir',
            'wird',
            'wirklich',
            'wirst',
            'wo',
            'woher',
            'wohin',
            'wohl',
            'wollen',
            'wollt',
            'wollte',
            'wollten',
            'worden',
            'wurde',
            'wurden',
            'waehrend',
            'während',
            'waehrenddem',
            'währenddem',
            'waehrenddessen',
            'währenddessen',
            'waere',
            'wäre',
            'wuerde',
            'würde',
            'wuerden',
            'würden',
            'z.b',
            'zehn',
            'zehnte',
            'zehnten',
            'zehnter',
            'zehntes',
            'zeit',
            'zu',
            'zuerst',
            'zugleich',
            'zum',
            'zunaechst',
            'zunächst',
            'zur',
            'zurueck',
            'zurück',
            'zusammen',
            'zwanzig',
            'zwar',
            'zwei',
            'zweite',
            'zweiten',
            'zweiter',
            'zweites',
            'zwischen',
            'zwoelf',
            'zwölf',
            'ueber',
            'über',
            'ueberhaupt',
            'überhaupt',
            'ubrigens',
            'übrigens',
        ];
}
