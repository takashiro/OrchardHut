document.write('<object id="scriptx" style="display:none" classid="clsid:1663ed61-23eb-11d2-b92f-008048fdd814" codebase="./plugin/scriptx/smsx.cab"></object>');

scriptx.print = function(){
	if(this.printing != undefined){
		this.printing.header = '';
		this.printing.footer = '';
		this.printing.leftMargin = 0;
		this.printing.topMargin = 0;
		this.printing.rightMargin = 0;
		this.printing.bottomMargin = 0;
		this.printing.Print(false);
	}else{
		window.print();
	}
}
