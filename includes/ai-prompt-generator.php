<?php
/**
 * AI Prompt Generator
 * Creates smart prompts for Gemini AI based on service and area
 */

class AIPromptGenerator {
    private $serviceCategories;
    private $areaData;
    
    public function __construct() {
        // Load service categories
        $this->serviceCategories = include dirname(__DIR__) . '/config/service-categories.php';
        // Load area data
        $this->areaData = include dirname(__DIR__) . '/config/area-data.php';
    }
    
    /**
     * Generate prompt for service+area page
     */
    public function generatePagePrompt($serviceName, $serviceSlug, $areaName, $areaSlug, $wordCount = 700) {
        // Get area details
        $areaInfo = $this->areaData[$areaSlug] ?? $this->areaData['default'];
        if (isset($this->areaData['default']) && $areaInfo === $this->areaData['default']) {
            $areaInfo['name'] = $areaName;
        }
        
        // Get related keywords for this service
        $relatedKeywords = $this->getRelatedKeywords($serviceName, $serviceSlug);
        
        // Build comprehensive prompt
        $prompt = $this->buildPrompt($serviceName, $serviceSlug, $areaName, $areaInfo, $relatedKeywords, $wordCount);
        
        return $prompt;
    }
    
    /**
     * Get related keywords for a service
     */
    private function getRelatedKeywords($serviceName, $serviceSlug) {
        $keywords = [];
        
        // Base variations
        $keywords[] = strtolower($serviceName);
        $keywords[] = str_replace('-', ' ', $serviceSlug);
        
        // Add context-specific keywords based on service type
        if (stripos($serviceName, 'pigeon') !== false) {
            $keywords = array_merge($keywords, ['balcony netting', 'bird control', 'pigeon safety nets', 'anti-bird nets', 'pigeon deterrents', 'UV-stabilized nets']);
        } elseif (stripos($serviceName, 'bird') !== false) {
            $keywords = array_merge($keywords, ['bird netting', 'anti-bird solutions', 'bird control nets', 'bird safety', 'industrial bird protection']);
        } elseif (stripos($serviceName, 'safety') !== false) {
            $keywords = array_merge($keywords, ['balcony safety', 'child safety', 'fall protection', 'construction safety', 'safety netting']);
        } elseif (stripos($serviceName, 'cricket') !== false) {
            $keywords = array_merge($keywords, ['practice nets', 'sports netting', 'cricket cage', 'indoor cricket', 'cricket training']);
        } elseif (stripos($serviceName, 'invisible grill') !== false) {
            $keywords = array_merge($keywords, ['SS grills', 'balcony grills', 'window grills', 'child safety grills', 'modern grills']);
        } elseif (stripos($serviceName, 'cloth hanger') !== false) {
            $keywords = array_merge($keywords, ['ceiling hangers', 'pulley system', 'clothes drying', 'laundry solutions', 'space-saving hangers']);
        }
        
        return array_unique($keywords);
    }
    
    /**
     * Build comprehensive AI prompt
     */
    private function buildPrompt($serviceName, $serviceSlug, $areaName, $areaInfo, $relatedKeywords, $wordCount) {
        // Detect service category and set specific context
        $serviceContext = $this->getServiceContext($serviceSlug);
        
        $prompt = <<<PROMPT
Write a unique, SEO-optimized {$wordCount}-word article about {$serviceName} installation services in {$areaName}, Chennai.

**CRITICAL: OUTPUT MUST BE PROPERLY FORMATTED HTML WITH STRUCTURE**

SERVICE DETAILS:
- Main Service: {$serviceName}
- Service Slug: {$serviceSlug}
- Related Services: {$this->formatKeywordList($relatedKeywords)}

**SERVICE-SPECIFIC FOCUS:**
{$serviceContext}

AREA DETAILS:
- Location: {$areaName}, Chennai
- Area Type: {$areaInfo['type']}
- Weather Conditions: {$areaInfo['weather']}
- Development Status: {$areaInfo['development']}
- Common Problems: {$areaInfo['problems']}
- Notable Landmarks: {$areaInfo['landmarks']}

**HTML FORMATTING REQUIREMENTS:**
- Use <h2> for main section headings (styled in blue)
- Use <h3> for sub-section headings
- Use <p> for paragraphs
- Use <ul> and <li> for bullet lists
- Use <strong> for important keywords and benefits
- NO <h1> tags (already used for page title)
- Clean, semantic HTML only

CONTENT STRUCTURE (Total: {$wordCount} words):

**SECTION 1: INTRODUCTION**
Format: <p>Introductory paragraph (100-120 words)</p>
- Hook readers with area-specific opening mentioning {$areaName}
- State the main service: {$serviceName}
- Explain why this service is essential in {$areaName}, Chennai
- Naturally mention 2-3 related keywords

**SECTION 2: BENEFITS OF OUR {$serviceName}** 
Format: <h2>Benefits of Our {$serviceName}</h2>
<ul><li><strong>Keyword:</strong> Description</li></ul>
- Create a bullet list with 6-8 key benefits
- Bold the benefit name/keyword
- Each item should be specific and valuable
- Focus on material quality, durability, safety features

**SECTION 3: {$areaName}'S UNIQUE CHALLENGES**
Format: <h2>{$areaName}'s Challenges with [SERVICE-SPECIFIC PROBLEM]</h2>
<p>Paragraph explaining challenges (150-180 words)</p>
- Use a heading relevant to THIS SERVICE (not generic "bird issues")
- For cricket nets: "Lack of Cricket Practice Facilities" or "Sports Ground Shortage"
- For pigeon nets: "Bird and Pigeon Menace" or "Balcony Bird Problems"  
- For invisible grills: "Safety Concerns for Children and Pets"
- For cloth hangers: "Space Constraints in Modern Homes"
- Discuss {$areaName}'s characteristics in context of THIS service
- Weather impact: {$areaInfo['weather']}
- Development type: {$areaInfo['type']}
- Local problems: {$areaInfo['problems']} (relate to service)
- Reference landmarks: {$areaInfo['landmarks']}

**SECTION 4: OUR SERVICE COVERAGE IN {$areaName}**
Format: <h2>Our Service Coverage in {$areaName}</h2>
<p>Paragraph about coverage (120-150 words)</p>
- Comprehensive coverage across {$areaName}
- Quick response time and local presence
- Understanding of area-specific needs
- Mention nearby landmarks for context

**SECTION 5: INSTALLATION PROCESS**
Format: <h2>Installation Process</h2>
<ol><li><strong>Step Name:</strong> Description</li></ol>
- Create numbered list with 4-5 installation steps
- Bold each step title (e.g., "Free Inspection:", "Quotation:", etc.)
- Brief description for each step
- Emphasize professional installation and quality

**SECTION 6: WHY RESIDENTS TRUST US**
Format: <h2>Why {$areaName} Residents Trust Us</h2>
<ul><li>Benefit with brief explanation</li></ul>
- List 5-6 trust factors
- Local expertise, experience
- Quality guarantee, warranty
- Customer satisfaction
- Professional team

**SECTION 7: FREQUENTLY ASKED QUESTIONS**
Format: <h2>Frequently Asked Questions</h2>
<h3>Q: Question here?</h3>
<p>A: Answer paragraph</p>
- Include 2-3 common questions
- Questions about installation time, cost, warranty
- Provide helpful, specific answers

**EXAMPLE OUTPUT FORMAT:**
```html
<p>Introduction paragraph with engaging hook about {$areaName} and {$serviceName}...</p>

<h2>Benefits of Our {$serviceName}</h2>
<ul>
<li><strong>100% Bird-Proof Protection:</strong> Complete solution description</li>
<li><strong>Durable HDPE Material:</strong> Weather-resistant details</li>
<li><strong>Professional Installation:</strong> Expert team benefits</li>
</ul>

<h2>{$areaName}'s Challenges</h2>
<p>Detailed paragraph about local challenges...</p>

<h2>Installation Process</h2>
<ol>
<li><strong>Free Inspection:</strong> Our team visits your location...</li>
<li><strong>Quotation:</strong> Instant pricing based on requirements...</li>
</ol>
```

**CRITICAL WRITING RULES:**
1. **MUST OUTPUT VALID HTML** - Use proper tags as shown in example
2. Professional, friendly, conversational tone
3. Short paragraphs (2-4 sentences)
4. Bold important keywords/benefits using <strong>
5. Use bullet lists for benefits/features
6. Use numbered lists for processes/steps
7. Natural keyword integration (no stuffing)
8. Specific to {$areaName} - mention landmarks, local context
9. Focus on solving customer problems
10. Make every sentence valuable

**REQUIREMENTS:**
- Word count: {$wordCount} words (±50 acceptable)
- Use "{$serviceName}" and "{$areaName}" naturally 5-8 times each
- Mention "Chennai" 3-5 times
- Include related keywords: {$this->formatKeywordList($relatedKeywords)}
- Reference weather: {$areaInfo['weather']}
- Reference problems: {$areaInfo['problems']}
- Reference landmarks: {$areaInfo['landmarks']}
- NO generic filler text
- Every section must have proper HTML structure

**CRITICAL OUTPUT INSTRUCTIONS:**
❌ DO NOT include ```html or ``` or any markdown code fences
❌ DO NOT include any explanatory text before or after the HTML
❌ DO NOT wrap content in markdown formatting
✅ Output ONLY clean, valid HTML code
✅ Start directly with <p> tag
✅ End with closing tags only

Make it unique, engaging, and perfectly structured for {$serviceName} services in {$areaName}, Chennai.

GENERATE PURE HTML NOW (NO MARKDOWN, NO CODE FENCES):
PROMPT;

        return $prompt;
    }
    
    /**
     * Get service-specific context based on service type
     */
    private function getServiceContext($serviceSlug) {
        // Cricket/Sports Nets
        if (stripos($serviceSlug, 'cricket') !== false || stripos($serviceSlug, 'sport') !== false || stripos($serviceSlug, 'box-cricket') !== false) {
            return "This is about SPORTS/CRICKET PRACTICE facilities. Focus on:
- Lack of playgrounds and cricket practice grounds in the area
- Need for cricket training facilities and box cricket setups
- People searching for sports grounds and practice nets nearby
- Installation for residential complexes, schools, sports academies
- Benefits for cricket enthusiasts and aspiring players
- DO NOT mention birds, pigeons, or bird problems";
        }
        
        // Pigeon/Bird Nets
        if (stripos($serviceSlug, 'pigeon') !== false || stripos($serviceSlug, 'bird') !== false || stripos($serviceSlug, 'kabutar') !== false) {
            return "This is about PIGEON/BIRD PROTECTION. Focus on:
- Pigeon and bird menace in balconies, windows, AC units
- Bird droppings, nesting problems, health hazards
- Protection from sparrows, crows, pigeons
- Keeping balconies and buildings clean and bird-free
- Preventing property damage from birds";
        }
        
        // Invisible Grills
        if (stripos($serviceSlug, 'invisible') !== false || stripos($serviceSlug, 'grill') !== false) {
            return "This is about SAFETY with INVISIBLE GRILLS. Focus on:
- Child safety - preventing falls from balconies/windows
- Pet safety - keeping cats, dogs safe
- Adult safety - protection for elderly
- Monkey protection - keeping monkeys out
- Modern aesthetic look without blocking views
- Can also prevent pigeons (secondary benefit)";
        }
        
        // Cloth Hangers
        if (stripos($serviceSlug, 'cloth') !== false || stripos($serviceSlug, 'hanger') !== false || stripos($serviceSlug, 'dry') !== false || stripos($serviceSlug, 'laundry') !== false) {
            return "This is about CLOTH DRYING SOLUTIONS. Focus on:
- Space-saving solutions for modern apartments
- Balcony and corridor cloth drying systems
- Pulley-based ceiling hangers
- Convenience and quick drying
- Maximizing limited space in urban homes
- Weather-resistant and durable materials";
        }
        
        // Monkey Safety Nets
        if (stripos($serviceSlug, 'monkey') !== false) {
            return "This is about MONKEY PROTECTION. Focus on:
- Monkey menace and attacks in the area
- Protection from aggressive monkeys
- Keeping food and belongings safe
- Residential and commercial protection
- Areas with heavy monkey population
- Safety for children and pets from monkeys";
        }
        
        // Balcony Safety Nets (multi-purpose)
        if (stripos($serviceSlug, 'balcony') !== false && stripos($serviceSlug, 'safety') !== false) {
            return "This is about BALCONY SAFETY (multi-purpose). Focus on:
- Child safety - preventing falls
- Pet safety - cats, dogs protection
- Pigeon and bird protection (secondary)
- Monkey protection (if relevant to area)
- Duct area coverage
- Multi-layer safety for families";
        }
        
        // Construction Safety Nets
        if (stripos($serviceSlug, 'construction') !== false || stripos($serviceSlug, 'industrial') !== false || stripos($serviceSlug, 'fall-protection') !== false) {
            return "This is about CONSTRUCTION/INDUSTRIAL SAFETY. Focus on:
- Worker safety at construction sites
- Fall protection for workers
- Building construction safety compliance
- Industrial facility safety measures
- Debris protection for pedestrians below
- OSHA and safety regulations";
        }
        
        // Duct Area Safety Nets
        if (stripos($serviceSlug, 'duct') !== false) {
            return "This is about DUCT AREA SAFETY. Focus on:
- Covering open duct areas in apartments
- Preventing accidental falls into duct spaces
- Child and pet safety around duct areas
- Keeping ducts clean and pest-free
- Building maintenance and compliance";
        }
        
        // Children Safety Nets
        if (stripos($serviceSlug, 'children') !== false || stripos($serviceSlug, 'child') !== false || stripos($serviceSlug, 'kids') !== false) {
            return "This is about CHILD SAFETY specifically. Focus on:
- Protecting children from balcony/window falls
- Safety for apartments with young kids
- Peace of mind for parents
- Covering all danger zones (balconies, stairs, terraces)
- Childproof solutions for homes";
        }
        
        // Pet Safety Nets
        if (stripos($serviceSlug, 'pet') !== false) {
            return "This is about PET SAFETY specifically. Focus on:
- Protecting cats and dogs from falling
- Safe balcony access for pets
- Pet-friendly materials
- Ventilation while ensuring safety
- Suitable for apartments with pets";
        }
        
        // Generic Safety Nets (fallback)
        return "This is about SAFETY SOLUTIONS. Focus on:
- General safety and protection needs
- Residential and commercial applications
- Quality materials and professional installation
- Multi-purpose safety benefits
- Area-specific safety requirements";
    }
    
    /**
     * Format keyword list for prompt
     */
    private function formatKeywordList($keywords) {
        return implode(', ', array_slice($keywords, 0, 8));
    }
}
