// Component AdminxPages

[[INCLUDE component=TrunkDouble]]
[[INCLUDE component=GraphicSimpleTree]]
[[INCLUDE component=Ajax]]
[[INCLUDE component=TrunkButton]]

(function(){
	'use strict';

	
	function AdminxPages() {
		
		this.dom = document.createElement('div');
		this.dom.setAttribute('component', 'AdminxPages');
		
		
		this.buildSkeleton();
			this.buildLeftMargin();
				this.buildButtons();
				this.buildTree();
		
		
		this.loadTree();
	}
	
	AdminxPages.prototype.buildSkeleton = function() {
		this.panels = trunk.create('Double');
		this.dom.appendChild(this.panels.dom);

		this.current = document.createElement('div');
		this.current.innerHTML = 'soy el current';
		this.current.classList.add('AdminxPages-current');
		this.panels.current_info.appendChild(this.current);
	};
	
	AdminxPages.prototype.buildLeftMargin = function() {
		this.left_margin = document.createElement('div');
		this.left_margin.className = 'TrunkMargin';
		this.panels.left.appendChild(this.left_margin);
	};

	AdminxPages.prototype.buildTree = function() {
		var that = this;
		
		var tree = this.tree = newGraphicSimpleTree();
		this.left_margin.appendChild(this.tree);

		tree.setCallbackDrop(function(event, origin, destination, place){

			var ajax = new Ajax('[[AJAX name=move_node]]');
			ajax.setCallback200(function(text){
				console.log(text);
				// Ñap solution:
				that.loadTree();
			});
			ajax.query({
				origin:origin,
				destination: destination,
				place: place,
			});
		});
		
		tree.setCallbackClick(function(event){
			event.stopPropagation();

			that.current.innerHTML = this.getText();
			that.panels.detailed(true);
		});

		tree.setCallbackDelete(function(event){
			event.stopPropagation();
			if (confirm('All subtree will be removed. Are you sure?')) {
				var node = this.parentNode.parentNode;
				var id = node.id;
				var ajax = new Ajax('[[AJAX name=delete_node]]');
				ajax.setCallback200(function(text){
					node.style.display = 'none';
				});
				ajax.query({'id':id});
			}
		});

	};

	AdminxPages.prototype.buildButtons = function() {
		var that = this;

		this.buttons_frame = document.createElement('div');
		this.buttons_frame.className = 'buttons-frame';
		this.left_margin.appendChild(this.buttons_frame);

		this.buttons = document.createElement('div');
		this.buttons.className = 'buttons';
		this.buttons_frame.appendChild(this.buttons);

		function addNode(type) {
			var current_node = that.tree.getSelected();

			var key = prompt('');
			if (null === key) {
				return;
			}
			
			var ajax = new Ajax('[[AJAX name=append_node]]');
			ajax.setCallback200(function(text){
				if ('' == text) {
					alert('Node could not be created');
				} else {
					var json = JSON.parse(text);
					that._loadTreeRec(key, current_node, json);
				}
			});
			ajax.query({
				'id': current_node.id,
				'key': key,
				'type': type,
			});
		}

		function addButton(name) {
			var cell = document.createElement('div');
			that.buttons.appendChild(cell);

			var button = trunk.create('Button');
			button.dom.textContent = name;
			cell.appendChild(button.dom);

			return button;
		}

		addButton('Add Page').dom.addEventListener('click', function(event) {
			addNode('page');
		}, true);
		addButton('Add PHP').dom.addEventListener('click', function(event) {
			addNode('php');
		}, true);
	};
	
	AdminxPages.prototype.loadTree = function() {
		var that = this;
		
		var ajax = new Ajax('[[AJAX name=load_tree]]');
		ajax.setCallback200(function(text) {
			var json = JSON.parse(text);
			that.tree.clear();
			that._loadTreeRec('ROOT', that.tree, json);


			that.tree.select('root');
		});
		ajax.query({});
	};
	
	
	AdminxPages.prototype._loadTreeRec = function(key, node, json) {
		var new_node = this.tree.createNode();
		new_node.id = json.id;
		new_node.setText(key);
		new_node.setAttribute('type', json.properties['type']);
		node.append(new_node);

		var children = json.children;

		for (var k in children) {
			this._loadTreeRec(k, new_node, children[k]);
		}
	};
	
	window.AdminxPages = AdminxPages;
	
})();