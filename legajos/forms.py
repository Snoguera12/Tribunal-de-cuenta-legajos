from django import forms
from .models import Legajo

class Legajoform(forms.ModelForm):
    class Meta:
        model = Legajo
        fields = ['n_legajo', 'nombre', 'dni', 'cargo']