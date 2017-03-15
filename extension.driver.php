<?php
	
	require_once(TOOLKIT . '/class.sectionmanager.php');
	
	class Extension_Duplicate_Entry extends Extension {
		
		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/backend/',
					'delegate'	=> 'InitaliseAdminPageHead',
					'callback'	=> 'initaliseAdminPageHead'
				)
			);
		}
		
		private function serialiseSectionSchema($section) {
			$current_section_fields = $section->fetchFieldsSchema();
			foreach($current_section_fields as $i => $field) {
				unset($current_section_fields[$i]['id']);
				unset($current_section_fields[$i]['location']);
			}
			return md5(serialize($current_section_fields));
		}

		public function initaliseAdminPageHead($context) {
			$page = Administration::instance()->Page;
			
			if ($page instanceof contentPublish) {
				
				$callback = Administration::instance()->getPageCallback();
				
				if($callback['context']['page'] !== 'edit') return;
				
				$sm = new SectionManager(Administration::instance());
				
				
				$current_section = $sm->fetch($sm->fetchIDFromHandle($callback['context']['section_handle']));
				$current_section_hash = $this->serialiseSectionSchema($current_section);
				
				$duplicate_sections = array();
				
				foreach($sm->fetch() as $section) {
					$section_hash = $this->serialiseSectionSchema($section);
					if ($section_hash == $current_section_hash && $section->get('handle')) {
						$duplicate_sections[$section->get('handle')] = $section->get('name');
					}
				}
				
				if (count($duplicate_sections) < 2) $duplicate_sections = NULL;

				$duplicateEntryConfig = Symphony::Configuration()->get('duplicate_entry');
				$sections = array_keys($duplicateEntryConfig);

				$configOptionToClearFields = array();
						
				$thisSection = $page->_context['section_handle'];
				// Only load on /publish/static-pages/ [this should be a variable]
				if ( in_array($thisSection , $sections) ){

					$configOptionToClearFields = $duplicateEntryConfig[$thisSection];

				}
				
				Administration::instance()->Page->addElementToHead(
					new XMLElement(
						'script',
						"Symphony.Context.add('duplicate-entry', " . json_encode(array(
							'current-section' => $callback['context']['section_handle'],
							'duplicate-sections' => $duplicate_sections,
							'clear-fields' => $configOptionToClearFields,
						)) . ");",
						array('type' => 'text/javascript')
					), time()
				);
				
				$page->addScriptToHead(URL . '/extensions/duplicate_entry/assets/duplicate_entry.js', 10001);
				
				// add particular css for SSM
				Administration::instance()->Page->addElementToHead(
					new XMLElement(
						'style',
						"body.inline.subsection #duplicate-entry { display: none; visibility: collapse }",
						array('type' => 'text/css')
					), time()+101
				);
			}
		}
	}
	
?>