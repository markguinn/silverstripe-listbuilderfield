<input type="hidden" name="$Title" value="$StringValue">

<div class="masterListWrapper listWrapper $extraClass">
	<% if $MasterTitle %>
		<h3>$MasterTitle</h3>
	<% end_if %>

	<ul class="masterList connectedSortable">
		<% loop $Options %>
			<li data-id="$Value" class="ui-state-default">$Title</li>
		<% end_loop %>
	</ul>
</div>

<div class="selectedListWrapper listWrapper $extraClass">
	<% if $SelectedTitle %>
		<h3>$SelectedTitle</h3>
	<% end_if %>

	<ul class="selectedList connectedSortable">
		<% loop $Selected %>
			<li data-id="$Value" class="ui-state-default">$Title</li>
		<% end_loop %>
	</ul>
</div>
