<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Savory-specific deterministic rules. Keeping this class separate prevents
 * recipe vocabulary and allergen policy from leaking into the generic router.
 */
class WaicFastSavoryDomainPack implements WaicFastDomainPackInterface {
	private $synonyms = array(
		'air' => array('air fryer'),
		'air fryer' => array('air fryer'),
		'asian' => array('asian', 'chinese', 'thai', 'teriyaki', 'soy', 'noodle', 'noodles'),
		'bbq' => array('bbq', 'barbecue', 'barbeque', 'grill', 'grilled', 'cookout'),
		'barbecue' => array('bbq', 'barbecue', 'barbeque', 'barbecued', 'grill', 'grilled', 'cookout'),
		'beef' => array('beef', 'steak', 'ground beef'),
		'breakfast' => array('breakfast', 'brunch', 'oat', 'oats', 'granola', 'smoothie', 'toast', 'pancake', 'egg', 'eggs', 'hash'),
		'broccoli' => array('broccoli', 'broccolini'),
		'chicken' => array('chicken', 'poultry'),
		'chocolate' => array('chocolate', 'cocoa', 'brownie', 'oreo'),
		'cookie' => array('cookie', 'cookies', 'bites', 'bars', 'oreo'),
		'cookies' => array('cookie', 'cookies', 'bites', 'bars', 'oreo'),
		'corn' => array('corn', 'cob'),
		'cut' => array('cut', 'knife', 'prep'),
		'dessert' => array('dessert', 'cake', 'cookie', 'cookies', 'brownie', 'bars', 'sweet'),
		'desserts' => array('dessert', 'cake', 'cookie', 'cookies', 'brownie', 'bars', 'sweet'),
		'dinner' => array('dinner', 'supper', 'entree', 'main dish', 'main course'),
		'dinners' => array('dinner', 'supper', 'entree', 'main dish', 'main course'),
		'fish' => array('fish', 'salmon', 'tuna', 'cod', 'trout', 'tilapia'),
		'gluten' => array('gluten free', 'gluten-free', 'wheat free', 'wheat-free'),
		'grilled' => array('grill', 'grilled', 'grilling', 'barbecue', 'cookout'),
		'grilling' => array('grill', 'grilled', 'grilling', 'barbecue', 'cookout'),
		'healthy' => array('healthy', 'light', 'fruit', 'protein', 'energy'),
		'herbs' => array('herb', 'herbs', 'fresh herbs'),
		'italian' => array('italian', 'sub', 'subs', 'pasta'),
		'kid' => array('kid friendly', 'kid-friendly', 'family friendly', 'family-friendly'),
		'quick' => array('quick', 'fast', 'easy', 'simple', 'weeknight'),
		'fast' => array('fast', 'quick', 'easy', 'simple', 'weeknight'),
		'lunch' => array('lunch', 'salad', 'sandwich', 'wrap'),
		'meal' => array('meal', 'meal prep', 'weeknight'),
		'menu' => array('menu', 'menus', 'meal plan'),
		'mexican' => array('mexican', 'tex mex', 'tex-mex', 'taco', 'tacos', 'enchilada', 'burrito', 'tostada'),
		'noodle' => array('noodle', 'noodles', 'ramen', 'rice noodles', 'pasta'),
		'noodles' => array('noodle', 'noodles', 'ramen', 'rice noodles', 'pasta'),
		'oven' => array('air fryer', 'skillet', 'stovetop', 'no oven'),
		'pasta' => array('pasta', 'spaghetti', 'linguine', 'penne', 'rigatoni', 'fettuccine', 'noodles'),
		'prep' => array('meal prep', 'big batch', 'batch cooking'),
		'produce' => array('produce', 'seasonal', 'in season', 'summer produce'),
		'rice' => array('rice', 'rice noodles', 'fried rice'),
		'salad' => array('salad', 'salads'),
		'seafood' => array('seafood', 'fish', 'salmon', 'tuna', 'shrimp', 'prawn', 'crab'),
		'side' => array('side', 'side dish', 'sides'),
		'snack' => array('snack', 'snacks', 'trail mix', 'bites', 'dip', 'school snack'),
		'snacks' => array('snack', 'snacks', 'trail mix', 'bites', 'dip', 'school snack'),
		'smoothie' => array('smoothie', 'smoothies', 'smoothie bowl', 'smoothie bowls'),
		'smoothies' => array('smoothie', 'smoothies', 'smoothie bowl', 'smoothie bowls'),
		'soup' => array('soup', 'stew', 'chowder', 'bisque'),
		'soups' => array('soup', 'soups', 'stew', 'chowder', 'bisque'),
		'spicy' => array('spicy', 'hot', 'chili', 'jalapeno', 'chipotle'),
		'stew' => array('stew', 'braise', 'slow cooker'),
		'summer' => array('summer', 'warm weather'),
		'taco' => array('taco', 'tacos', 'tostada', 'tostadas'),
		'tacos' => array('taco', 'tacos', 'tostada', 'tostadas'),
		'thanksgiving' => array('thanksgiving', 'holiday'),
		'vegetable' => array('vegetable', 'vegetables', 'veggie', 'veggies', 'kabob', 'kabobs'),
		'vegetables' => array('vegetable', 'vegetables', 'veggie', 'veggies', 'kabob', 'kabobs'),
		'winter' => array('winter', 'cold weather'),
		'vegan' => array('vegan', 'plant based', 'plant-based'),
		'vegetarian' => array('vegetarian', 'meatless', 'plant based', 'plant-based'),
	);

	private $stopwords = array(
		'a', 'about', 'added', 'and', 'any', 'are', 'article', 'articles', 'avoid', 'can', 'could', 'do', 'for', 'from', 'give', 'guide',
		'guides', 'hacks', 'have', 'help', 'how', 'i', 'idea', 'ideas',
		'in', 'is', 'it', 'make', 'me', 'my', 'of', 'on', 'please', 'recipe', 'recipes', 'show', 'some', 'something', 'suggest', 'that',
		'the', 'this', 'tip', 'tips', 'to', 'want', 'what', 'with', 'would', 'week', 'today', 'tonight', 'under', 'ready', 'minutes', 'minute',
		'min', 'mins', 'no', 'not', 'without',
		'free', 'plan', 'planning', 'same', 'similar', 'but', 'than', 'less', 'more', 'get', 'find', 'need', 'should', 'cook', 'cooking',
	);

	public function getCode() {
		return 'savory';
	}

	/**
	 * Bump when indexed facets or strict local classification rules change.
	 * WaicFastIndexer uses this opportunistically to rebuild stale task indexes.
	 */
	public function getIndexVersion() {
		return 8;
	}

	public function getLabel() {
		return __('Savory recipes and articles', 'ai-copilot-content-generator');
	}

	public function getDefaults() {
		return array(
			'post_types' => array('recipe', 'article'),
			'taxonomies' => array('course', 'dish', 'cuisine', 'dietary', 'occasion', 'season', 'time-of-day'),
			'meta_fields' => array('ready_in_time', 'grouped_ingredients*description'),
			'card_taxonomy' => 'course',
		);
	}

	public function parse( $message, $context, $config ) {
		$raw = $this->normalize($message);
		$raw = $this->applyTypos($raw);
		if ($raw === '' || $this->isOutOfScope($raw)) {
			return array('mode' => 'decline');
		}
		$previous = $this->previousQuestion($context);
		$isFollowUp = $previous !== '' && preg_match('/^(same|similar|another|with|without|under|faster|quicker|even|make it|but)/', $raw);
		$effective = $isFollowUp ? trim($previous . ' ' . $raw) : $raw;
		if (!$isFollowUp && $this->isVague($raw)) {
			return array('mode' => 'clarify', 'raw' => $raw);
		}

		$isArticle = (bool) preg_match('/\b(article|articles|guide|tips|hacks|how to|how do|what is|what are|storage|store|difference between)\b/', $effective);
		$isRecipe = (bool) preg_match('/\b(recipes?|meals?|dishes?|dinners?|suppers?|lunch(?:es)?|breakfasts?|brunch(?:es)?|desserts?|snacks?|bbq|barbecue|soups?|stews?|salads?|pasta|tacos?|chicken|beef|pork|fish|seafood|salmon|broccoli|vegetarian|vegan|gluten|cuisine|cook|make)\b/', $effective);
		if (!$isArticle && !$isRecipe) {
			return array('mode' => 'decline');
		}

		$hardAbsent = $this->hardAbsentFlags($effective);
		if ($this->hasSafetyConflict($effective, $hardAbsent)) {
			return array('mode' => 'clarify', 'raw' => $raw, 'conflict' => 1);
		}
		$postTypes = $isArticle ? array('article') : array('recipe');
		$postTypes = array_values(array_intersect($postTypes, (array) $config['post_types']));
		if (empty($postTypes)) {
			return array('mode' => 'decline');
		}
		$maxMinutes = $this->parseMinutes($raw);
		if (!$maxMinutes && $isFollowUp) {
			$maxMinutes = $this->parseMinutes($effective);
		}

		$literalExcludes = $this->literalExcludes($effective);
		$ingredients = $this->extractIngredients($raw);
		$groups = array();
		if (!empty($ingredients)) {
			foreach ($ingredients as $ingredient) {
				$groups[] = $this->synonymGroup($ingredient);
			}
		}
		$tokens = preg_split('/\s+/', $effective, -1, PREG_SPLIT_NO_EMPTY);
		foreach ((array) $tokens as $token) {
			if ($this->isStopword($token) || is_numeric($token) || $this->isConstraintToken($token) || $this->isHardAbsentToken($token, $hardAbsent) ||
				($maxMinutes && in_array($token, array('quick', 'fast', 'faster', 'quicker'), true)) ||
				$this->isExcludedToken($token, $literalExcludes)) {
				continue;
			}
			$groups[] = $this->synonymGroup($token);
		}
		$groups = $this->uniqueGroups($groups);
		$groups = array_slice($groups, 0, WaicFastPath::MAX_QUERY_GROUPS);
		if (empty($groups) && !$isRecipe && !$isArticle) {
			return array('mode' => 'decline');
		}

		$meal = $this->mealType($effective);
		$cuisine = preg_match('/\b(mexican|italian|asian|thai|chinese|indian|mediterranean)\b/', $effective, $match) ? $match[1] : '';
		$season = preg_match('/\b(spring|summer|fall|autumn|winter)\b/', $effective, $match) ? ($match[1] === 'autumn' ? 'fall' : $match[1]) : '';
		$occasion = preg_match('/\b(bbq|barbecue|thanksgiving|christmas|easter|halloween|birthday|party|picnic)\b/', $effective, $match) ? $match[1] : '';
		$defaults = array(
			'meal' => (!$isArticle && $meal === '') ? 'dinner' : '',
			'season' => (!$isArticle && $season === '' && $occasion === '') ? $this->currentSeason() : '',
		);
		$requiredHits = empty($groups) ? 0 : (!empty($ingredients) ? min(count($ingredients), count($groups)) : min(3, count($groups)));
		$candidateTerms = array();
		foreach ($groups as $group) {
			foreach (array_slice($group, 0, 2) as $term) {
				$candidateTerms[] = $term;
			}
		}

		return array(
			'mode' => 'search',
			'raw' => $raw,
			'effective' => $effective,
			'post_types' => $postTypes,
			'groups' => $groups,
			'candidate_terms' => array_values(array_unique($candidateTerms)),
			'required_group_hits' => $requiredHits,
			'hard_absent' => $hardAbsent,
			'literal_excludes' => $literalExcludes,
			'max_minutes' => $maxMinutes,
			'meal' => $meal,
			'cuisine' => $cuisine,
			'season' => $season,
			'occasion' => $occasion,
			'defaults' => $defaults,
			'is_article' => $isArticle,
			'is_follow_up' => $isFollowUp,
		);
	}

	public function buildFacets( $post, $document, $config ) {
		unset($config);
		$ingredients = array();
		$ready = 0;
		foreach ((array) $document['meta_map'] as $key => $values) {
			if ('ready_in_time' === $key && isset($values[0])) {
				$ready = absint($values[0]);
			}
			if (false !== stripos($key, 'ingredient')) {
				$ingredients = array_merge($ingredients, (array) $values);
			}
		}
		$ingredientText = $this->normalize(implode(' ', $ingredients));
		$terms = $this->normalize($document['terms_text']);
		$title = $this->normalize($document['title']);
		$known = ('recipe' === $post->post_type && $ingredientText !== '');
		$risk = array(
			'added_sugar' => null,
			'nuts' => null,
			'dairy' => null,
			'meat' => null,
			'alcohol' => null,
			'gluten' => null,
			'shellfish' => null,
			'finfish' => null,
			'egg' => null,
			'soy' => null,
			'coconut' => null,
		);
		if ($known) {
			$haystack = $title . ' ' . $ingredientText;
			$risk['added_sugar'] = $this->matchesRisk($haystack, 'added_sugar');
			$risk['nuts'] = $this->matchesRisk($haystack, 'nuts');
			$risk['dairy'] = $this->matchesRisk($haystack, 'dairy');
			$risk['meat'] = $this->matchesRisk($haystack, 'meat');
			$risk['alcohol'] = $this->matchesRisk($haystack, 'alcohol');
			$risk['gluten'] = $this->matchesRisk($haystack, 'gluten');
			$risk['shellfish'] = $this->matchesRisk($haystack, 'shellfish');
			$risk['finfish'] = $this->matchesRisk($haystack, 'finfish');
			$risk['egg'] = $this->matchesRisk($haystack, 'egg');
			$risk['soy'] = $this->matchesRisk($haystack, 'soy');
			$risk['coconut'] = $this->matchesRisk($haystack, 'coconut');
		}
		$this->applyDietaryLabels($risk, $terms . ' ' . $title);
		return array(
			'classifier_version' => $this->getIndexVersion(),
			'ingredients_known' => $known,
			'ingredient_count' => count($ingredients),
			'ready_minutes' => $ready,
			'risk' => $risk,
		);
	}

	public function filterRow( $row, $plan ) {
		$facets = isset($row['facets']) ? $row['facets'] : array();
		$risk = isset($facets['risk']) && is_array($facets['risk']) ? $facets['risk'] : array();
		foreach ((array) $plan['hard_absent'] as $flag) {
			if (!array_key_exists($flag, $risk) || false !== $risk[$flag]) {
				return false;
			}
		}
		$ready = isset($facets['ready_minutes']) ? absint($facets['ready_minutes']) : 0;
		if (!empty($plan['max_minutes']) && (!$ready || $ready > absint($plan['max_minutes']))) {
			return false;
		}
		$search = isset($row['search_text']) ? $row['search_text'] : '';
		foreach ((array) $plan['literal_excludes'] as $term) {
			if (WaicFastIndex::containsTerm($search, $term)) {
				return false;
			}
		}
		return true;
	}

	public function scoreRow( $row, $plan ) {
		$text = $this->normalize($row['title'] . ' ' . $row['terms_text'] . ' ' . $row['excerpt']);
		$terms = $this->normalize($row['terms_text']);
		$score = 0;
		if (!empty($plan['is_article'])) {
			$score += 'article' === $row['post_type'] ? 10 : -20;
		} else {
			$score += 'recipe' === $row['post_type'] ? 6 : -10;
		}
		if (!empty($plan['meal'])) {
			$score += $this->mealScore($plan['meal'], $text, $terms);
		} elseif (!empty($plan['defaults']['meal'])) {
			$score += $this->mealScore($plan['defaults']['meal'], $text, $terms) * 0.4;
		}
		if (!empty($plan['cuisine']) && WaicFastIndex::containsTerm($text, $plan['cuisine'])) {
			$score += 10;
		}
		if (!empty($plan['season']) && WaicFastIndex::containsTerm($terms, $plan['season'])) {
			$score += 8;
		} elseif (!empty($plan['defaults']['season']) && WaicFastIndex::containsTerm($terms, $plan['defaults']['season'])) {
			$score += 3;
		}
		if (!empty($plan['occasion']) && (WaicFastIndex::containsTerm($text, $plan['occasion']) || ('barbecue' === $plan['occasion'] && WaicFastIndex::containsTerm($text, 'bbq')))) {
			$score += 10;
		}
		if (!empty($plan['max_minutes'])) {
			$ready = absint(isset($row['facets']['ready_minutes']) ? $row['facets']['ready_minutes'] : 0);
			$score += $ready ? 6 + max(0, min(5, (absint($plan['max_minutes']) - $ready) / 5)) : 0;
		}
		if (!preg_match('/\b(drink|cocktail|mocktail|beverage|smoothie)\b/', $plan['effective']) && preg_match('/\b(cocktail|mocktail|spritz|martini|margarita|sangria)\b/', $text)) {
			$score -= 15;
		}
		return $score;
	}

	public function renderAnswer( $plan, $count ) {
		$type = !empty($plan['is_article']) ? _n('article', 'articles', $count, 'ai-copilot-content-generator') : _n('recipe', 'recipes', $count, 'ai-copilot-content-generator');
		$answer = sprintf(_n('I found %1$d Savory %2$s that matches your request.', 'I found %1$d Savory %2$s that match your request.', $count, 'ai-copilot-content-generator'), $count, $type);
		if (!empty($plan['hard_absent'])) {
			$answer .= ' ' . __('The index filters are strict, but please verify the full ingredient list for your needs.', 'ai-copilot-content-generator');
		}
		return '<p>' . esc_html($answer) . '</p>';
	}

	public function renderClarification( $plan ) {
		if (!empty($plan['conflict'])) {
			return '<p>' . esc_html__('Your request contains conflicting ingredient or dietary requirements. Which requirement should I prioritize?', 'ai-copilot-content-generator') . '</p>';
		}
		return '<p>' . esc_html__('What kind of recipe would you like? Add a meal, ingredient, cuisine, dietary need, or time limit.', 'ai-copilot-content-generator') . '</p>';
	}

	public function renderNoResults( $plan ) {
		$text = __('I could not find a strong match in the published Savory index. Try removing one preference or adding a different ingredient.', 'ai-copilot-content-generator');
		if (!empty($plan['hard_absent'])) {
			$text = __('The strict ingredient filters removed the available matches. Try a broader request, and always verify the full ingredient list.', 'ai-copilot-content-generator');
		}
		return '<p>' . esc_html($text) . '</p>';
	}

	private function normalize( $text ) {
		return WaicFastIndex::normalizeText($text);
	}

	private function applyTypos( $query ) {
		$map = array(
			'with out' => 'without',
			'desrt' => 'dessert',
			'desert' => 'dessert',
			'reciept' => 'recipe',
			'receipt' => 'recipe',
			'receipe' => 'recipe',
			'recipie' => 'recipe',
			'recpie' => 'recipe',
			'brocoli' => 'broccoli',
			'gluton' => 'gluten',
			'suggar' => 'sugar',
			'suger' => 'sugar',
			'meet' => 'meat',
			'withouth' => 'without',
			'vegateales' => 'vegetables',
			'vegatables' => 'vegetables',
			'vegitables' => 'vegetables',
			'vegatales' => 'vegetables',
			'orions' => 'onions',
			'onios' => 'onions',
			'oniuns' => 'onions',
			// Common Savory audience aliases; the input was normalized before this map runs.
			'sin' => 'without',
			'sans' => 'without',
			'lactosa' => 'lactose',
			'cebolla' => 'onion',
			'pollo' => 'chicken',
			'pescado' => 'fish',
			'cena' => 'dinner',
			'desayuno' => 'breakfast',
			'almuerzo' => 'lunch',
			'postre' => 'dessert',
			'mas rapido' => 'quick',
			'diner' => 'dinner',
		);
		foreach ($map as $wrong => $right) {
			$query = preg_replace('/\b' . preg_quote($wrong, '/') . '\b/', $right, $query);
		}
		return $query;
	}

	private function isOutOfScope( $query ) {
		return (bool) preg_match('/\b(weather|forecast|rain|snow|exchange rate|currency|bitcoin|crypto|stock price|sports score|lottery|horoscope|flight status|hotel booking|system prompt|api key)\b/', $query);
	}

	private function isVague( $query ) {
		if (in_array($query, array('suggest a recipe', 'suggest recipe', 'give me a recipe', 'recipe for me', 'what should i cook', 'what should i make', 'i am hungry', 'im hungry'), true)) {
			return true;
		}
		$tokens = array_filter(preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY), array($this, 'notStopword'));
		return count($tokens) < 1;
	}

	public function notStopword( $token ) {
		return !$this->isStopword($token);
	}

	private function isStopword( $token ) {
		return strlen($token) < 2 || in_array($token, $this->stopwords, true);
	}

	private function isConstraintToken( $token ) {
		return in_array($token, array('gluten', 'dairy', 'lactose', 'nut', 'nuts', 'sugar', 'vegan', 'vegetarian', 'allergic', 'allergy'), true);
	}

	private function isHardAbsentToken( $token, $flags ) {
		$map = $this->negativeRiskAliases();
		$token = $this->normalize($token);
		foreach ((array) $flags as $flag) {
			if (isset($map[$flag]) && in_array($token, $map[$flag], true)) {
				return true;
			}
		}
		return false;
	}

	private function synonymGroup( $term ) {
		$term = $this->normalize($term);
		$group = isset($this->synonyms[$term]) ? $this->synonyms[$term] : array($term);
		$group[] = $term;
		return array_values(array_unique(array_filter(array_map(array($this, 'normalize'), $group))));
	}

	private function uniqueGroups( $groups ) {
		$out = array();
		$seen = array();
		foreach ((array) $groups as $group) {
			$group = array_values(array_unique(array_filter((array) $group)));
			$key = implode('|', $group);
			if ($key !== '' && !isset($seen[$key])) {
				$seen[$key] = true;
				$out[] = $group;
			}
		}
		return $out;
	}

	private function previousQuestion( $context ) {
		$history = isset($context['history']) ? (array) $context['history'] : array();
		for ($i = count($history) - 1; $i >= 0; $i--) {
			$question = isset($history[$i]['question']) ? $this->normalize($history[$i]['question']) : '';
			if ($question !== '') {
				return $question;
			}
		}
		return '';
	}

	private function extractIngredients( $query ) {
		$ingredients = array();
		if (preg_match('/\b(?:with|i have|using)\s+(.+)$/', $query, $match)) {
			$tail = preg_replace('/\b(?:under|in less than|ready in)\s+\d+.*$/', '', $match[1]);
			foreach (preg_split('/\s*(?:,|\band\b|\bplus\b)\s*/', $tail, -1, PREG_SPLIT_NO_EMPTY) as $value) {
				$value = trim($this->normalize($value));
				if ($value !== '' && count(preg_split('/\s+/', $value)) <= 3 && !$this->isStopword($value)) {
					$ingredients[] = $value;
				}
			}
		}
		return array_slice(array_values(array_unique($ingredients)), 0, 4);
	}

	private function hardAbsentFlags( $query ) {
		$flags = array();
		$patterns = array(
			'gluten' => '/\b(gluten[- ]free|without gluten|no gluten|free of gluten|celiac|allergic to (?:gluten|wheat))\b/',
			'dairy' => '/\b(dairy[- ]free|lactose[- ]free|without dairy|no dairy|free of dairy|sin lactosa|allergic to (?:dairy|milk))\b/',
			'nuts' => '/\b(nut[- ]free|peanut[- ]free|tree[- ]nut[- ]free|without (?:tree )?nuts?|no (?:tree )?nuts?|free of (?:tree )?nuts?|allergic to (?:tree )?nuts?|allergic to peanuts?)\b/',
			'added_sugar' => '/\b(sugar[- ]free|without (?:added )?sugar|no (?:added )?sugar|no sugar added|free of (?:added )?sugar)\b/',
			'alcohol' => '/\b(alcohol[- ]free|without alcohol|no alcohol|free of alcohol)\b/',
			'shellfish' => '/\b(shellfish[- ]free|without shellfish|no shellfish|free of shellfish|allergic to (?:shellfish|shrimp|prawns?))\b/',
			'finfish' => '/\b(fish[- ]free|finfish[- ]free|without (?:fin)?fish|no (?:fin)?fish|free of (?:fin)?fish|allergic to (?:fin)?fish)\b/',
			'coconut' => '/\b(coconut[- ]free|without coconut|no coconut|free of coconut|allergic to coconut)\b/',
			'egg' => '/\b(egg[- ]free|without eggs?|no eggs?|free of eggs?|allergic to eggs?)\b/',
		);
		foreach ($patterns as $flag => $pattern) {
			if (preg_match($pattern, $query)) {
				$flags[$flag] = $flag;
			}
		}
		// Ingredient aliases are safety constraints only when the visitor uses an
		// explicit negative marker. This preserves positive searches such as
		// "tofu dinner" while making "without milk" and "soy-free" strict.
		foreach ($this->negativeRiskAliases() as $flag => $aliases) {
			foreach ($aliases as $alias) {
				if ($this->negativeAliasPresent($query, $alias)) {
					$flags[$flag] = $flag;
					break;
				}
			}
		}
		if (preg_match('/\b(vegan)\b/', $query)) {
			foreach (array('meat', 'finfish', 'shellfish', 'dairy', 'egg') as $flag) {
				$flags[$flag] = $flag;
			}
		} elseif (preg_match('/\b(vegetarian|without meat|no meat|meat[- ]free|meatless)\b/', $query)) {
			foreach (array('meat', 'finfish', 'shellfish') as $flag) {
				$flags[$flag] = $flag;
			}
		}
		if (preg_match('/\b(no seafood|without seafood|seafood[- ]free)\b/', $query)) {
			$flags['finfish'] = 'finfish';
			$flags['shellfish'] = 'shellfish';
		}
		return array_values($flags);
	}

	/**
	 * Canonical, deterministic aliases used only for explicitly negative food
	 * constraints. Unknown ingredients remain unknown rather than being treated
	 * as safe by the local index.
	 */
	private function negativeRiskAliases() {
		return array(
			'added_sugar' => array('sugar', 'added sugar', 'brown sugar', 'syrup', 'honey', 'agave', 'molasses', 'sweetened'),
			'nuts' => array('nut', 'nuts', 'tree nut', 'tree nuts', 'almond', 'almonds', 'walnut', 'walnuts', 'pecan', 'pecans', 'cashew', 'cashews', 'hazelnut', 'hazelnuts', 'pistachio', 'pistachios', 'peanut', 'peanuts', 'macadamia', 'macadamias', 'nut butter', 'praline'),
			'dairy' => array('dairy', 'lactose', 'milk', 'cream', 'butter', 'cheese', 'yogurt', 'yoghurt', 'ice cream', 'cream cheese', 'sour cream', 'ghee', 'whey', 'casein', 'custard', 'mascarpone', 'gelato', 'kefir', 'paneer', 'ricotta', 'feta', 'mozzarella', 'parmesan'),
			'meat' => array('meat', 'meats', 'animal protein'),
			'alcohol' => array('alcohol', 'rum', 'wine', 'vodka', 'whiskey', 'bourbon', 'liqueur', 'beer', 'guinness', 'brandy', 'kahlua', 'baileys', 'sherry', 'marsala', 'vanilla extract', 'mirin', 'sake'),
			'gluten' => array('gluten', 'wheat', 'flour', 'bread', 'cracker', 'cookie', 'cake', 'pastry', 'breadcrumb', 'barley', 'rye', 'couscous', 'farro', 'orzo', 'semolina', 'bulgur', 'seitan', 'ladyfinger', 'ladyfingers'),
			'shellfish' => array('shellfish', 'shrimp', 'prawn', 'prawns', 'crab', 'lobster', 'clam', 'mussel', 'oyster', 'scallop'),
			'finfish' => array('fish', 'finfish', 'salmon', 'tuna', 'cod', 'cobia', 'halibut', 'tilapia', 'trout', 'anchovy', 'anchovies', 'sardine', 'sardines', 'mahi mahi'),
			'egg' => array('egg', 'eggs', 'egg white', 'egg whites', 'egg yolk', 'egg yolks', 'whole egg', 'eggnog', 'egg nog', 'omelet', 'omelette', 'frittata', 'quiche', 'aioli', 'mayonnaise', 'mayo', 'meringue'),
			'soy' => array('soy', 'tofu', 'edamame', 'soy sauce', 'miso'),
			'coconut' => array('coconut', 'coconut milk', 'coconut cream', 'coconut oil', 'coconut flakes', 'coconut flour', 'coconut water', 'cream of coconut', 'shredded coconut'),
		);
	}

	private function negativeAliasPresent( $query, $alias ) {
		$alias = $this->normalize($alias);
		if ($alias === '') {
			return false;
		}
		$body = str_replace(' ', '\\s+', preg_quote($alias, '/'));
		$marker = '(?:no|without|avoid|hold|allergic\\s+to|allergy\\s+to|do\\s+not\\s+want|dont\\s+want|don\\s+t\\s+want)';
		if (preg_match('/(?:^|\\s)' . $body . '\\s+free(?:\\s|$)/', $query)
			|| preg_match('/(?:^|\\s)free\\s+of\\s+' . $body . '(?:\\s|$)/', $query)
			|| preg_match('/(?:^|\\s)' . $marker . '\\s+(?:any\\s+|the\\s+)?' . $body . '(?:\\s|$)/', $query)) {
			return true;
		}
		// A single negative marker can govern a short list, e.g. "without
		// milk and peanuts". Stop at a new search clause so unrelated words do
		// not become safety requirements.
		if (preg_match_all('/(?:^|\\s)' . $marker . '\\s+(?:any\\s+|the\\s+)?([^.;!?]{0,90})/', $query, $scopes)) {
			foreach ((array) $scopes[1] as $scope) {
				$scope = preg_replace('/\\b(?:on|for|with|in|as)\\b.*$/', ' ', (string) $scope);
				if (preg_match('/(?:^|\\s)' . $body . '(?:\\s|$)/', (string) $scope)) {
					return true;
				}
			}
		}
		return false;
	}

	private function hasSafetyConflict( $query, $flags ) {
		$query = preg_replace('/\b(?:meat|fish|shellfish|seafood)[- ]free\b|\b(?:without|no)\s+(?:meat|fish|shellfish|seafood)\b/', '', $query);
		$patterns = array(
			'meat' => '/\b(chicken|beef|pork|bacon|ham|turkey|sausage|steak|lamb)\b/',
			'finfish' => '/\b(salmon|tuna|cod|trout|tilapia|fish)\b/',
			'shellfish' => '/\b(shrimp|prawn|crab|lobster|clam|mussel|oyster|scallop|shellfish)\b/',
		);
		foreach ((array) $flags as $flag) {
			if (isset($patterns[$flag]) && preg_match($patterns[$flag], $query)) {
				return true;
			}
		}
		return false;
	}

	private function literalExcludes( $query ) {
		$map = array(
			'chicken' => array('chicken', 'poultry'),
			'beef' => array('beef', 'steak'),
			'salmon' => array('salmon'),
			'shrimp' => array('shrimp', 'prawn', 'prawns'),
			'crab' => array('crab'),
			'lobster' => array('lobster'),
			'pork' => array('pork', 'bacon', 'ham', 'salami', 'chorizo', 'pancetta', 'prosciutto'),
			'pasta' => array('pasta', 'spaghetti', 'linguine', 'penne', 'rigatoni', 'fettuccine'),
			'onion' => array('onion', 'onions'),
			'mushroom' => array('mushroom', 'mushrooms'),
			'banana' => array('banana', 'bananas'),
			'garlic' => array('garlic'),
			'cheese' => array('cheese', 'mozzarella', 'parmesan', 'feta', 'ricotta'),
			'cilantro' => array('cilantro', 'coriander'),
			'bell pepper' => array('bell pepper', 'bell peppers'),
			'soy' => array('soy', 'tofu', 'edamame', 'soy sauce', 'miso'),
			'peanut' => array('peanut', 'peanuts', 'peanut butter'),
			'milk' => array('milk'),
		);
		$out = array();
		foreach ($map as $label => $terms) {
			if (preg_match('/\b(?:without|no|avoid|allergic to)\s+(?:any\s+)?' . preg_quote($label, '/') . 's?\b/', $query)) {
				$out = array_merge($out, $terms);
			}
		}
		return array_values(array_unique($out));
	}

	private function isExcludedToken( $token, $excludes ) {
		foreach ((array) $excludes as $exclude) {
			if (WaicFastIndex::containsTerm($exclude, $token) || WaicFastIndex::containsTerm($token, $exclude)) {
				return true;
			}
		}
		return false;
	}

	private function parseMinutes( $query ) {
		$wordMinutes = array(
			'three quarters of an hour' => 45,
			'three quarter hour' => 45,
			'quarter of an hour' => 15,
			'quarter hour' => 15,
			'half an hour' => 30,
			'half hour' => 30,
			'an hour' => 60,
			'one hour' => 60,
		);
		foreach ($wordMinutes as $phrase => $minutes) {
			if (WaicFastIndex::containsTerm($query, $phrase)) {
				return $minutes;
			}
		}
		if (preg_match('/\b(?:under|within|less than|below|ready in|in)\s+(\d{1,3})\s*(?:minutes?|mins?|min)\b/', $query, $match)) {
			return max(1, min(360, absint($match[1])));
		}
		if (preg_match('/\b(\d{1,3})[\s-]+minute\b/', $query, $match) || preg_match('/\b(\d{1,3})\s*(?:minutes?|mins?|min)\b/', $query, $match)) {
			return max(1, min(360, absint($match[1])));
		}
		if (preg_match('/\b(?:under|within|less than|below|ready in|in)\s+(\d{1,2})\s*hours?\b/', $query, $match) || preg_match('/\b(\d{1,2})\s*hours?\b/', $query, $match)) {
			return max(1, min(360, absint($match[1]) * 60));
		}
		return 0;
	}

	private function mealType( $query ) {
		foreach (array('breakfast', 'brunch', 'lunch', 'dinner', 'supper', 'dessert', 'snack') as $meal) {
			if (preg_match('/\b' . $meal . 's?\b/', $query)) {
				return 'supper' === $meal ? 'dinner' : $meal;
			}
		}
		return '';
	}

	private function mealScore( $meal, $text, $terms ) {
		$score = 0;
		if ('dinner' === $meal) {
			$score += preg_match('/\b(dinner|supper|entree|main dish|main course)\b/', $terms . ' ' . $text) ? 10 : 0;
			$score -= preg_match('/\b(breakfast|cocktail|mocktail)\b/', $terms . ' ' . $text) ? 12 : 0;
		} elseif ('breakfast' === $meal || 'brunch' === $meal) {
			$score += preg_match('/\b(breakfast|brunch|oat|granola|smoothie|toast|pancake)\b/', $terms . ' ' . $text) ? 12 : -8;
			$score -= preg_match('/\b(dinner|entree|cocktail|mocktail)\b/', $terms . ' ' . $text) ? 10 : 0;
		} elseif ('dessert' === $meal) {
			$score += preg_match('/\b(dessert|cake|cookie|brownie|pudding|parfait|sweet)\b/', $terms . ' ' . $text) ? 12 : -8;
		} else {
			$score += WaicFastIndex::containsTerm($terms . ' ' . $text, $meal) ? 9 : 0;
		}
		return $score;
	}

	private function currentSeason() {
		$month = (int) current_time('n');
		if ($month >= 3 && $month <= 5) {
			return 'spring';
		}
		if ($month >= 6 && $month <= 8) {
			return 'summer';
		}
		if ($month >= 9 && $month <= 11) {
			return 'fall';
		}
		return 'winter';
	}

	private function matchesRisk( $text, $flag ) {
		$text = $this->normalize($text);
		if ('dairy' === $flag) {
			$text = preg_replace('/\b(peanut|almond|cashew|cocoa) butter\b/', '', $text);
		}
		if ('added_sugar' === $flag) {
			$text = preg_replace('/\b(?:sugar free|no (?:added )?sugar|without (?:added )?sugar|no sugar added)\b/', '', $text);
		}
		$patterns = array(
			'added_sugar' => '/\b(sugars?|syrups?|honey|molasses|agave|jams?|jell(?:y|ies)|sweetened|condensed milk|ice cream|gelato|chocolate milk|vanilla yogurt|cookie dough|cake mix|angel food cake|pound cake|chocolate chips?|baking chocolate|white chocolate|semi sweet chocolate|semisweet chocolate|fudge|brownie mix|dark chocolate fudge|duncan hines|nutella|hazelnut spread|hoisin sauce|oreo cookies?|oreos?|sprinkles?|dessert sandwiches?|cheesecake|lemon curd|bordeaux cookies?|dried cranberries?|preserves|ladyfingers?|cand(?:y|ies)|caramel|frosting|icing|marshmallows?)\b/',
			'nuts' => '/\b(peanuts?|almonds?|walnuts?|pecans?|cashews?|pistachios?|hazelnuts?|macadamias?|tree nuts?|nut butter|praline)\b/',
			'dairy' => '/\b(milk|cheese|butter|cream|yogurt|yoghurt|ice cream|cream cheese|sour cream|ghee|whey|casein|custard|mascarpone|gelato|kefir|paneer|ricotta|feta|mozzarella|parmesan)\b/',
			'meat' => '/\b(chicken|beef|pork|lamb|bacon|ham|sausages?|turkey|veal|prosciutto|gelatin|salami|steak|meatballs?|pepperoni|pancetta|chorizo)\b/',
			'alcohol' => '/\b(rum|wine|vodka|whisk(?:ey|y)|bourbon|liqueur|beer|guinness|brandy|kahlua|baileys|sherry|marsala|prosecco|tequila|vanilla extract|mirin|sake)\b/',
			'gluten' => '/\b(flour|wheat|bread|pasta|spaghetti|noodles|crackers?|cookies?|cakes?|pastr(?:y|ies)|breadcrumbs?|barley|rye|couscous|farro|orzo|semolina|bulgur|seitan|ladyfingers?)\b/',
			'shellfish' => '/\b(shrimp|prawns?|crabs?|lobsters?|clams?|mussels?|oysters?|scallops?|shellfish)\b/',
			'finfish' => '/\b(fish|finfish|salmon|tuna|cod|cobia|halibut|tilapia|trout|anchov(?:y|ies)|sardines?|mahi mahi)\b/',
			'egg' => '/\b(eggs?|egg whites?|egg yolks?|whole egg|eggnog|egg nog|omelets?|omelettes?|frittata|quiche|aioli|mayonnaise|mayo|meringue)\b/',
			'soy' => '/\b(soy|tofu|edamame|soy sauce|miso)\b/',
			'coconut' => '/\b(coconut|coconut milk|coconut cream|coconut oil|coconut flakes|coconut flour|coconut water|cream of coconut|shredded coconut)\b/',
		);
		return !empty($patterns[$flag]) && (bool) preg_match($patterns[$flag], $text);
	}

	private function applyDietaryLabels( &$risk, $text ) {
		if (preg_match('/\bgluten[- ]free\b/', $text) && true !== $risk['gluten']) {
			$risk['gluten'] = false;
		}
		if (preg_match('/\bdairy[- ]free|lactose[- ]free\b/', $text) && true !== $risk['dairy']) {
			$risk['dairy'] = false;
		}
		if (preg_match('/\bnut[- ]free|peanut[- ]free\b/', $text) && true !== $risk['nuts']) {
			$risk['nuts'] = false;
		}
		if (preg_match('/\b(sugar[- ]free|no added sugar)\b/', $text) && true !== $risk['added_sugar']) {
			$risk['added_sugar'] = false;
		}
		if (preg_match('/\balcohol[- ]free\b/', $text) && true !== $risk['alcohol']) {
			$risk['alcohol'] = false;
		}
		if (preg_match('/\bsoy[- ]free\b/', $text) && true !== $risk['soy']) {
			$risk['soy'] = false;
		}
		if (preg_match('/\bvegan\b/', $text)) {
			foreach (array('meat', 'finfish', 'shellfish', 'dairy', 'egg') as $flag) {
				if (true !== $risk[$flag]) {
					$risk[$flag] = false;
				}
			}
		} elseif (preg_match('/\bvegetarian\b/', $text)) {
			foreach (array('meat', 'finfish', 'shellfish') as $flag) {
				if (true !== $risk[$flag]) {
					$risk[$flag] = false;
				}
			}
		}
	}
}
